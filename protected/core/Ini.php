<?php

class Ini {

	public static function readIni($filename) {
		return parse_ini_file($filename, true);
	}

	public static function writeIni($filename, $ini) {
        $string = '';
        foreach(array_keys($ini) as $key) {
            $string .= '['.$key."]\n";
            $string .= Ini::stringify($ini[$key], '') . "\n";
        }
        file_put_contents($filename, $string);
    }
	
	public static function mergeIni($ini1, $ini2) {
		$keys = array_merge(array_keys($ini1), array_keys($ini2));
		$marged = array();
		
		foreach( $keys as $k ) {
			$v1 = array_key_exists($k, $ini1) ? $ini1[$k] : array();
			$v2 = array_key_exists($k, $ini2) ? $ini2[$k] : array();
			$merged[$k] = array_merge($v1, $v2);
		}
		
		return $merged;
	}

    private static function stringify($ini, $prefix) {
        $string = '';
        ksort($ini);
        foreach($ini as $key => $val) {
            if (is_array($val)) {
                $string .= Ini::stringify($ini[$key], $prefix.$key.'.');
            } else {
                $string .= $prefix.$key.' = '.str_replace("\n", "\\\n", Ini::value($val))."\n";
            }
        }
        return $string;
    }

    private static function value($val) {
        if ($val === true)
			return 'true';
        else if ($val === false)
			return 'false';
		else if ($val === null)
			return 'null';
		else
			return $val;
    }
	
}
