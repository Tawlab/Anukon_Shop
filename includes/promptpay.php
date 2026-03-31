<?php
// PromptPay Payload Generator
class PromptPay {
    public static function generatePayload($target, $amount = null) {
        $target = preg_replace('/[^0-9]/', '', $target);
        if (strlen($target) >= 13) {
            $type = '02'; // NID / Tax ID
        } else {
            $type = '01'; // Mobile
            if (strlen($target) == 10 && substr($target, 0, 1) == '0') {
                $target = '0066' . substr($target, 1);
            }
        }
        
        $target_len = str_pad(strlen($target), 2, '0', STR_PAD_LEFT);
        $merchant_info = "0016A000000677010111" . $type . $target_len . $target;
        
        $merchant_len = str_pad(strlen($merchant_info), 2, '0', STR_PAD_LEFT);
        
        $payload = "00020101021129" . $merchant_len . $merchant_info . "5802TH5303764";
        
        if ($amount !== null && $amount > 0) {
            $amount_str = number_format($amount, 2, '.', '');
            $amount_len = str_pad(strlen($amount_str), 2, '0', STR_PAD_LEFT);
            $payload .= "54" . $amount_len . $amount_str;
        }
        
        $payload .= "6304";
        $payload .= self::crc16($payload);
        return $payload;
    }

    private static function crc16($data) {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
    }
}
?>
