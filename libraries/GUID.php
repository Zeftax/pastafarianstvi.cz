<?php
function GenerateUUID()
{
	$data = $data ?? random_bytes(16);

	assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return $data;
}

function uuid_to_bin($uuid)
{
	return hex2bin(str_replace('-', '', $uuid));
}

function bin_to_uuid($value)
{
	$string = bin2hex($value);
 
	return preg_replace('/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/', '$1-$2-$3-$4-$5', $string);
}
?>