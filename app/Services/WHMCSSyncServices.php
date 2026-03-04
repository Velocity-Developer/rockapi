<?php

namespace App\Services;

use App\Models\WhmcsDomain;
use App\Services\WHMCSCustomService;

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
        }

        return count($domains);
    }
}
