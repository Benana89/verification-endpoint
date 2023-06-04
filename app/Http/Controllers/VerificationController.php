<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class VerificationController extends Controller
{

    public function verify(Request $request)
    {

        /**
         * Condition 1: JSON has a valid recipient
         * - `recipient` must have `name` and `email`
         * - error code: `invalid_recipient`
         */
        $result = 'verified';
        if (!$request->has(['data.recipient.name', 'data.recipient.email'])) {
            $result = 'invalid_recipient';
        }

        /**
         * Condition 2: JSON has a valid issuer
         * - `issuer` must have `name` and `identityProof`
         * - The value of `issuer.identityProof.key` (i.e. Ethereum wallet address) must be found in
         * the DNS TXT record of the domain name specified by `issuer.identityProof.location`
         */
        if (!$request->has(['data.issuer.name', 'data.issuer.identityProof']) || !$request->has(['data.issuer.identityProof.key', 'data.issuer.identityProof.location'])) {
            $result = 'invalid_issuer';
        } else {
            $lookUpURL = 'https://dns.google/resolve?name=' . $request->input('data.issuer.identityProof.location') . '&type=TXT';
            $response = Http::get($lookUpURL);
            if ($response->successful()) {
                $answerData = $response->json()['Answer'];
                $found = false;
                foreach ($answerData as $record) {
                    if (strpos($record['data'], $request->input('data.issuer.identityProof') !== false)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $result = 'invalid_issuer';
                }
            }

        }

        /**
         * Condition 3: JSON has a valid signature
         *
         */

        $flatten = Arr::dot($request->data);
        $hashArray = [];

        foreach ($flatten as $key => $value) {
            $update = '{"' . $key . '":"' . $value . '"}';
            array_push($hashArray, hash('sha256', $update));
        }
        sort($hashArray, SORT_STRING);
        $arrayToString = implode('', $hashArray);
        $targetHash = hash('sha256', $arrayToString);

        if (!$request->has(['signature', 'signature.targetHash']) || $request->input('signature.targetHash') !== $targetHash) {
            $result = 'invalid_signature';
        }

        return response()->json([
            'data' => [
                'issuer' => 'Accredify',
                'result' => $result
            ]

            ,
        ], 200);
        ;
    }
}