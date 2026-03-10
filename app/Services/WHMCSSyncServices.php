<?php

namespace App\Services;

use App\Models\WhmcsDomain;
use App\Models\WhmcsHosting;
use App\Models\WhmcsUser;
use App\Services\WHMCSCustomService;
use Carbon\Carbon;

class WHMCSSyncServices
{
    /**
     * sync domain expired from WHMCS
     */
    public function syncDomainExpired($month = null)
    {
        // mengambil data domain expired dari WHMCS
        $domains = (new WHMCSCustomService())->getDomainsExpiry($month);

        //if success = false
        if (isset($domains['success']) && $domains['success'] === false) {
            return 0;
        }

        //if data is empty
        if (empty($domains['data'])) {
            return 0;
        }

        $domains = $domains['data'] ?? [];

        // menyimpan data domain ke tabel whmcs_domains
        foreach ($domains as $domain) {
            WhmcsDomain::updateOrCreate(
                ['whmcs_id' => $domain['id']],
                [
                    'whmcs_userid' => $domain['userid'],
                    'domain' => $domain['domain'],
                    'expirydate' => $domain['expirydate'],
                    'registrationdate' => $domain['registrationdate'],
                    'nextduedate' => $domain['nextduedate'],
                    'type' => $domain['type'],
                    'status' => $domain['status'],
                    'registrar' => $domain['registrar'],
                    'user_email' => $domain['user_email'],
                ]
            );

            // menyimpan data user ke tabel whmcs_users
            WhmcsUser::updateOrCreate(
                ['whmcs_id' => $domain['userid']],
                [
                    'email' => $domain['user_email'],
                    'firstname' => $domain['user_first_name'],
                    'lastname' => $domain['user_last_name'],
                ]
            );

            if (empty($domain['hosting_id'])) {
                continue;
            }

            // menyimpan data hosting ke tabel whmcs_hostings
            WhmcsHosting::updateOrCreate(
                ['whmcs_id' => $domain['hosting_id']],
                [
                    'whmcs_userid' => $domain['hosting_userid'],
                    'domain' => $domain['hosting_domain'],
                    'nextduedate' => $domain['hosting_nextduedate'],
                    'billingcycle' => $domain['hosting_billingcycle'],
                    'domainstatus' => $domain['hosting_domainstatus'],
                    'package_name' => $domain['hosting_package_name'],
                    'package_servertype' => $domain['hosting_package_servertype'],
                    'package_name_id' => $domain['hosting_package_name_id'],
                ]
            );
        }

        return count($domains);
    }

    /**
     * sync hosting expired from WHMCS
     */
    public function syncHostingExpired($start, $end)
    {

        // mengambil data hosting expired dari WHMCS
        $hostings = (new WHMCSCustomService())->getHostingsExpiry($start, $end);

        //if success = false
        if (isset($hostings['success']) && $hostings['success'] === false) {
            return 0;
        }

        //if data is empty
        if (empty($hostings['data'])) {
            return 0;
        }

        $hostings = $hostings['data'] ?? [];

        // menyimpan data hosting ke tabel whmcs_hostings
        foreach ($hostings as $hosting) {
            WhmcsHosting::updateOrCreate(
                ['whmcs_id' => $hosting['id']],
                [
                    'whmcs_userid' => $hosting['userid'],
                    'domain' => $hosting['domain'],
                    'nextduedate' => $hosting['nextduedate'],
                    'billingcycle' => $hosting['billingcycle'],
                    'domainstatus' => $hosting['domainstatus'],
                    'package_name' => $hosting['package_name'],
                    'package_servertype' => $hosting['package_servertype'],
                    'package_name_id' => $hosting['package_name_id'],
                ]
            );
        }

        return $hostings;
    }
}
