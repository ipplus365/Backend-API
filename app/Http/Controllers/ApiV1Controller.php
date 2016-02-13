<?php

namespace App\Http\Controllers;

use App\Models\ASN;
use App\Models\IPv4BgpPrefix;
use App\Models\IPv6BgpPrefix;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\ApiBaseController;

class ApiV1Controller extends ApiBaseController
{
    /*
     * URI: /asn/{as_number}
     * Optional Params: with_raw_whois
     */
    public function asn(Request $request, $as_number)
    {
        // lets only use the AS number
        $as_number = str_ireplace('as', '', $as_number);

        $asnData = ASN::with('emails')->where('asn', $as_number)->first();

        if (is_null($asnData)) {
            $data = $this->makeStatus('Could not find ASN', false);
            return $this->respond($data);
        }

        $output['asn']  = $asnData->asn;
        $output['name'] = $asnData->name;
        $output['description_short'] = $asnData->description;
        $output['description_full']  = $asnData->description_full;
        $output['country_code']         = $asnData->counrty_code;
        $output['website']              = $asnData->website;
        $output['email_contacts']       = $asnData->email_contacts;
        $output['abuse_contacts']       = $asnData->abuse_contacts;
        $output['looking_glass']        = $asnData->looking_glass;
        $output['traffic_estimation']   = $asnData->traffic_estimation;
        $output['traffic_ratio']        = $asnData->traffic_ratio;
        $output['owner_address']        = $asnData->owner_address;

        if ($request->has('with_raw_whois') === true) {
            $output['raw_whois'] = $asnData->raw_whois;
        }

        $output['date_updated']        = (string) $asnData->updated_at;
        return $this->sendData($output);
    }

    /*
     * URI: /asn/{as_number}/prefixes
     */
    public function asnPrefixes($as_number)
    {
        // lets only use the AS number
        $as_number = str_ireplace('as', '', $as_number);

        $ipv4Prefixes = IPv4BgpPrefix::where('asn', $as_number)->get();
        $ipv6Prefixes = IPv6BgpPrefix::where('asn', $as_number)->get();

        $output['asn'] = (int) $as_number;

        $output['ipv4_prefixes'] = [];
        foreach ($ipv4Prefixes as $prefix) {
            $prefixWhois = $prefix->whois;

            $prefixOutput['prefix']         = $prefix->ip . '/' . $prefix->cidr;
            $prefixOutput['ip']             = $prefix->ip;
            $prefixOutput['cidr']           = $prefix->cidr;

            $prefixOutput['name']           = isset($prefixWhois->name) ? $prefixWhois->name : null;
            $prefixOutput['description']    = isset($prefixWhois->description) ? $prefixWhois->description : null;
            $prefixOutput['country_code']   = isset($prefixWhois->counrty_code) ? $prefixWhois->counrty_code : null;

            $output['ipv4_prefixes'][]  = $prefixOutput;
            $prefixOutput = null;
            $prefixWhois = null;
        }

        $output['ipv6_prefixes'] = [];
        foreach ($ipv6Prefixes as $prefix) {
            $prefixWhois = $prefix->whois;

            $prefixOutput['prefix'] = $prefix->ip . '/' . $prefix->cidr;
            $prefixOutput['ip']     = $prefix->ip;
            $prefixOutput['cidr']   = $prefix->cidr;

            $prefixOutput['name']           = isset($prefixWhois->name) ? $prefixWhois->name : null;
            $prefixOutput['description']    = isset($prefixWhois->description) ? $prefixWhois->description : null;
            $prefixOutput['country_code']   = isset($prefixWhois->counrty_code) ? $prefixWhois->counrty_code : null;

            $output['ipv6_prefixes'][]  = $prefixOutput;
            $prefixOutput = null;
            $prefixWhois = null;
        }

        return $this->sendData($output);
    }

    /*
     * URI: /prefix/{ip}/{cidr}
     * Optional Params: with_raw_whois
     */
    public function prefix(Request $request, $ip, $cidr)
    {
        $ipVersion = $this->ipUtils->getInputType($ip);

        if ($ipVersion === 4) {
            $prefix = IPv4BgpPrefix::where('ip', $ip)->where('cidr', $cidr)->first();
        } else if ($ipVersion === 6) {
            $prefix = IPv6BgpPrefix::where('ip', $ip)->where('cidr', $cidr)->first();
        } else {
            $data = $this->makeStatus('Malformed input', false);
            return $this->respond($data);
        }

        if (is_null($prefix) === true) {
            $data = $this->makeStatus('Count not find prefix', false);
            return $this->respond($data);
        }

        $prefixWhois = $prefix->whois();
        $allocation = $this->ipUtils->getAllocationEntry($prefix->ip);
        $geoip = $this->ipUtils->geoip($prefix->ip);

        $output['prefix']           = $prefix->ip . '/' . $prefix->cidr;
        $output['ip']               = $prefix->ip;
        $output['cidr']             = $prefix->cidr;
        $output['asn']              = $prefix->asn;
        $output['name']             = $prefixWhois ? $prefixWhois->name : null;
        $output['description_short']= $prefixWhois ? $prefixWhois->description : null;
        $output['description_full'] = $prefixWhois ? $prefixWhois->description_full : null;
        $output['emails']           = $prefixWhois ? $prefixWhois->email_contacts : null;
        $output['abuse_emails']     = $prefixWhois ? $prefixWhois->abuse_contacts : null;
        $output['owner_address']    = $prefixWhois ? $prefixWhois->owner_address : null;

        $output['country_codes']['whois_country_code']          = $prefixWhois ? $prefixWhois->counrty_code : null;
        $output['country_codes']['rir_allocation_country_code'] = $allocation ? $allocation->counrty_code : null;
        $output['country_codes']['maxmind_country_code']        = $geoip->country->isoCode ?: null;

        $output['rir_allocation']['rir_name']           = $allocation->rir->name;
        $output['rir_allocation']['country_code']       = $allocation->counrty_code;
        $output['rir_allocation']['allocated_prefix']   = $allocation->ip . '/' . $allocation->cidr;
        $output['rir_allocation']['date_allocated']     = $allocation->date_allocated . ' 00:00:00';

        $output['maxmind']['country_code']  = $geoip->country->isoCode ?: null;
        $output['maxmind']['city']          = $geoip->city->name ?: null;

        if ($request->has('with_raw_whois') === true) {
            $output['raw_whois'] = $prefixWhois ? $prefixWhois->raw_whois : null;
        }

        $output['date_updated']   = $prefixWhois ? (string) $prefixWhois->updated_at : $prefix->updated_at;

        return $this->sendData($output);

    }
}