<?php

namespace App\Http\Traits;

trait GeneratorTrait
{
    public function generateRandomColor(): string
    {
        return '#'.str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    public function getRawText($contract, $verify = false): string
    {
        // For Provincial Contracts, Cebu & Davao - Removed 20240226 by Sean Philip Cruz
        /*if ($verify) {
            return $this->linebreak(9). $this->tab(9) . '    ' . $contract['number'].
                $this->linebreak(2) . $this->tab(9) . date('F d Y', strtotime($contract['created_at'])).
                $this->linebreak(2) . $this->tab(3) . $contract['station'].
                $this->linebreak(2) . $this->tab(3) . $contract->Advertiser->name.
                $this->linebreak(2) . 'Address: ' . $contract->Agency->address .
                $this->linebreak(1) . 'Contact No: ' . $contract->Agency->contact_number.
                $this->linebreak(2) . $contract->Agency->name . $this->tabBreaker($contract->Agency->name) . $contract['product'].
                $this->linebreak(7) . $contract['detail'].
                $this->linebreak($this->lineBreaker($contract['detail'])) . $this->tab(8) . '     ' . $this->generateCost($contract['total_prod'], $contract['prod_cost'], $contract['prod_cost_vat']).
                "\r\n". $this->generateCashEx($contract['manila_prod'], $contract['cebu_prod'], $contract['davao_prod'],'Manila','Cebu','Davao', $contract['total_prod']).
                "\r\n". $this->tab(6) .'     '. $this->generateCost($contract['total_amount'], $contract['package_cost'], $contract['package_cost_vat']).
                "\r\n". $this->generateCashEx($contract['manila_cash'], $contract['cebu_cash'], $contract['davao_cash'],'Manila','Cebu','Davao', $contract['total_amount']).
                "\r\n". $this->generateCashEx($contract['manila_ex'], $contract['cebu_ex'], $contract['davao_ex'],'Exdeal','Exdeal','Exdeal', $contract['total_ex']).
                $this->linebreak(11). date("M d Y", strtotime($contract['commencement'])) . $this->tab(4) . date("M d Y", strtotime($contract['end_of_broadcast'])).
                $this->linebreak(4). date("M d Y", strtotime($contract['bo_date'])) ."\t\t". $contract['bo_number'].
                $this->linebreak(4). $contract->Advertiser->name.$this->tab(5). $contract->Agency->name.
                $this->linebreak(9). $this->tab(1) .date("M d Y")."\t\t\t".'AE/'. $contract['ae'] . $this->tab(2) . $this->consignee($contract['number']);
        }*/
        $text =
            // Contract Number
            $this->linebreak(7).$this->tab(9).'    '.$contract['number'].
            //  Date Created
            $this->linebreak(2).$this->tab(9).date('F d Y', strtotime($contract['created_at'])).
            // Station
            $this->linebreak(2).$this->tab(3).$contract['station'].
            // Advertiser w/ Tab Breaker
            $this->linebreak(2).$this->tab(3).$contract->Advertiser->name.
            $this->linebreak(3).$contract->Agency->name.$this->tabBreaker($contract->Agency->name).$this->checkProductName($contract['product']).
            // Contract Details
            $this->linebreak(5).$contract['detail'].
            $this->linebreak($this->lineBreaker($contract['detail']));

        // Append the costs
        $appendedCosts = $this->appendCosts($text, $contract);

        // Append the footer
        return $this->appendFooter($appendedCosts, $contract);
    }

    public function appendCosts($text, $contract): string
    {
        // Check if Production Cost values are valid
        $hasProductionCost = ! empty($contract['prod_cost']);

        // Check if Package Cost + VAT values are valid
        $hasPackageCost = ! empty($contract['package_cost']);

        // Check if Generating Total Cash values are valid
        $hasCash = (
            (! empty($contract['manila_cash']) && $contract['manila_cash'] != 0.00) ||
            (! empty($contract['cebu_cash']) && $contract['cebu_cash'] != 0.00) ||
            (! empty($contract['davao_cash']) && $contract['davao_cash'] != 0.00)
        );

        // Check if Exchange Deals are valid
        $hasExchange = (
            (! empty($contract['manila_ex']) && $contract['manila_ex'] != 0.00) ||
            (! empty($contract['cebu_ex']) && $contract['cebu_ex'] != 0.00) ||
            (! empty($contract['davao_ex']) && $contract['davao_ex'] != 0.00)
        );

        $hasProd = (
            (! empty($contract['manila_prod']) && $contract['manila_prod'] != 0.00) ||
            (! empty($contract['cebu_prod']) && $contract['cebu_prod'] != 0.00) ||
            (! empty($contract['davao_prod']) && $contract['davao_prod'] != 0.00)
        );

        if ($hasProductionCost) {
            $text .= $this->tab(8).'     '.$this->generateCost(
                $contract['total_prod'],
                $contract['prod_cost'],
                $contract['prod_cost_vat']
            );

            if ($hasProd) {
                $text .= "\r\n".$this->generateCashEx(
                    $contract['manila_prod'],
                    $contract['cebu_prod'],
                    $contract['davao_prod'],
                    false, false, false,
                    $contract['total_prod']
                );
            }

        }

        if ($hasPackageCost) {
            $text .= "\r\n".$this->tab(8).'   '.$this->generateCost(
                $contract['total_amount'],
                $contract['package_cost'],
                $contract['package_cost_vat']
            );
        }

        if ($hasCash) {
            $text .= "\r\n".$this->generateCashEx(
                $contract['manila_cash'],
                $contract['cebu_cash'],
                $contract['davao_cash'],
                false, false, false,
                $contract['total_amount']
            );
        }

        if ($hasExchange) {
            $text .= "\r\n".$this->generateCashEx(
                $contract['manila_ex'],
                $contract['cebu_ex'],
                $contract['davao_ex'],
                true, true, true,
                $contract['total_ex']
            );
        }

        return $text;
    }

    public function appendFooter($text, $contract): string
    {
        return $text .= // Commencement and End of Broadcast
            $this->linebreak(4).date('M d Y', strtotime($contract['commencement'])).$this->tab(4).date('M d Y', strtotime($contract['end_of_broadcast'])).
            // BO Date and Number
            $this->linebreak(2).date('M d Y', strtotime($contract['bo_date']))."\t\t".$contract['bo_number'].
            // Receiving of Advertiser and Agency
            $this->linebreak(3).$contract->Advertiser->name.$this->tab(5).$contract->Agency->name.
            // Date, AE, and Consignee
            $this->linebreak(6).$this->tab(1).date('M d Y')."\t\t\t".'AE/'.$contract['ae'].$this->tab(3).$this->consignee($contract['number']);
    }

    // For generating linebreaks in the text files
    public function linebreak($num): string
    {
        $end = '';

        for ($i = 0; $i < $num; $i++) {
            $end .= "\n";
        }

        return $end;
    }

    public function tab($num): string
    {
        $tabs = '';

        for ($i = 0; $i < $num; $i++) {
            $tabs .= "\t";
        }

        return $tabs;
    }

    public function consignee($contract_number): string
    {
        $manilaToCebu = strpos($contract_number, 'BT02');
        $manilaToDavao = strpos($contract_number, 'CT02');
        $manilaToCebuDavao = strpos($contract_number, 'BTCT02');
        $cebu = strpos($contract_number, 'CEB');
        $davao = strpos($contract_number, 'DAV');

        /*if($manilatocebu !== false || $manilaToCebu !== false || $manilaToCebuDavao !== false) {
            return 'CECILIA C. BARREIRO';
        } elseif ($manilaToDavao !== false) {
            return 'ANTONIO V. BARREIRO JR.';
        } else if($cebu !== false) {
            return 'ANTONIO V. BARREIRO JR.';
        } else if($davao !== false) {
            return 'ANTONIO V. BARREIRO JR.';
        } else {
            // $consignee = 'LUIS MARI V. BARREIRO';
            return 'CEL DEL VALLE';
        }*/

        // 20240212 - Edited by Sean Philip Cruz request by Sir Jon to change the consignee to CEL DEL VALLE
        return 'CEL DEL VALLE';
    }

    public function lineBreaker($string = null): int
    {
        $newLines = substr_count($string, "\n");
        $baseLine = 14;

        if ($newLines > $baseLine) {
            return $newLines - $baseLine;
        } elseif ($newLines < $baseLine) {
            return $baseLine - $newLines;
        } else {
            return 0;
        }
    }

    public function tabBreaker($string = null): string
    {
        $stringCount = strlen($string);

        if ($stringCount >= 12) {
            return $this->tab(6);
        } else {
            return $this->tab(7).'    ';
        }
    }

    public function checkProductName($productName): string
    {
        $stringCount = strlen($productName);

        if ($stringCount > 10) {
            return $this->tab(1).'     '.$productName;
        } else {
            return $this->tab(2).$productName;
        }
    }

    public function checkLocal($string)
    {
        $targets = ['DAV', 'CEB'];

        foreach ($targets as $t) {
            // Source code: https://stackoverflow.com/questions/19178295/check-if-string-contains-one-of-several-words
            // String checker
            if (strpos($string, $t) !== false) {
                return true;
            }
        }
    }

    public function generateCost($total, $cost, $vat): string
    {
        $totalProd = '';
        $productionCost_VAT = '';

        if ($total != 0.00) {
            $totalProd = $cost;

            if ($vat == 'VATINC') {
                $productionCost_VAT = 'VAT-Inclusive';
            } elseif ($vat == 'VATEX') {
                $productionCost_VAT = 'VAT-Exclusive';
            } elseif ($vat == 'NONVAT') {
                $productionCost_VAT = 'NON-VAT';
            }
        }

        // 20250322 Added by Sean Philip Cruz; Added to remove extra spaces.
        if ($productionCost_VAT != '') {
            return $totalProd."\r\n".$this->tab(9).'   '.$productionCost_VAT;
        }

        return $totalProd;
    }

    public function generateCashEx($manilaVal = 0.00, $cebuVal = 0.00, $davaoVal = 0.00, $isManilaExDeal = false, $isCebuExDeal = false, $isDavaoExDeal = false, $total = 0.00): string
    {
        $mnlExDeal = $isManilaExDeal ?? 'Ex-Deal';
        $cbuExDeal = $isCebuExDeal ?? 'Ex-Deal';
        $davExDeal = $isDavaoExDeal ?? 'Ex-Deal';

        // For Manila
        if ($manilaVal != 0.00 && $cebuVal == 0.00 && $davaoVal == 0.00) {
            if ($isManilaExDeal) {
                $generatedManila = 'Ex-Deal'.' '.number_format($manilaVal, 2).' ';
            } else {
                $generatedManila = "\t";
            }
        } elseif ($manilaVal != 0.00) {
            $generatedManila = $mnlExDeal.' '.number_format($manilaVal, 2).' ';
        } else {
            $generatedManila = "\t\t".'      ';
        }

        // For Cebu
        if ($cebuVal != 0.00) {
            $generatedCebu = $cbuExDeal.' '.number_format($cebuVal, 2).' ';
        } else {
            $generatedCebu = "\t\t".'   ';
        }

        // For Davao
        if ($davaoVal != 0.00) {
            $generatedDavao = $davExDeal.' '.number_format($davaoVal, 2).' ';
        } else {
            $generatedDavao = "\t\t";
        }

        // Computing Total
        if ($total != 0.00) {
            $generatedTotal = number_format($total, 2);
        } else {
            $generatedTotal = '';
        }

        return $generatedManila.$generatedCebu.$generatedDavao.$this->tab(4).'   '.$generatedTotal;
    }
}
