<?php
// Helper to format currency in Indian numbering system
function formatIndianCurrency($val) {
    $is_negative = $val < 0;
    $val = abs($val);
    $decimal = sprintf("%.2f", $val);
    $arr = explode('.', $decimal);
    $num = $arr[0];
    $dec = isset($arr[1]) ? $arr[1] : '00';
    
    $last_three = substr($num, -3);
    $rest = substr($num, 0, -3);
    if ($rest != '') {
        $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest) . ",";
    }
    $formatted = '₹ ' . $rest . $last_three . '.' . $dec;
    return $is_negative ? '-' . $formatted : $formatted;
}

// Alias helper for dashboard compatibility
function formatCurrency($val) {
    return formatIndianCurrency($val);
}
?>
