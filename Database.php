<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    function buildQuery(string $query, array $args = []): string {
        $query = preg_replace_callback('/\?([dfa])?|#|\{([^\{\}]*|(?R))*\}/', function($match) use (&$args) {
            if ($match[0][0] == '{') { 
                $block = substr($match[0], 1, -1);
                $skip = array_shift($args);
                preg_replace_callback('/\?([df])?/', function($m) use (&$args, &$skip) {
                    $value = array_shift($args);var_dump($value);
                    switch ($m[1]) {
                        case 'd':
                            $value = (int)$value;
                            break;
                        case 'f':
                            $value = (float)$value;
                            break;
                        default:
                            if (!is_scalar($value)) {
                                throw new Exception('Invalid parameter value');
                            }
                            break;
                    }
                    return $value;
                }, $block);var_dump($block);
                return $skip ? '' : $block;
                /*if(!$skip)
                    return false;
                else
                    return ' AND block = 1';*/
            } else if ($match[0] == '#') {
                if (isset($args[0])) {
                    $value = array_shift($args);
                    if (is_array($value)) {
                        if (empty($value)) {
                            throw new Exception('Empty list');
                        }
                        $i = 0; 
                        while ($i<count($value)){
                            if(is_string($value[$i])){
                                $value[$i]="`$value[$i]`";
                            }
                            $i=$i+1;
                        }
                        $v = implode(', ', $value);
                        return $v;
                    } else {
                        return $value;
                    }
                } else {
                    throw new Exception('Not enough parameters');
                }
            } else { 
                if (isset($args[0]) && !is_array($args[0])) {
                    $value = array_shift($args);
                    if ($value === null) {
                        return 'NULL';
                    } else {
                        if(isset($match[1])){
                            switch ($match[1]) {
                                case 'd':
                                    return (int)$value;
                                case 'f':
                                    return (float)$value;
                                default:
                                    if (!is_scalar($value)) {
                                        throw new Exception('Wrong value');
                                    }
                                    return $value;
                            }
                        }else { 
                            if(!is_array($value)){
                                $st = "'$value'";
                                return $st;
                            }
                            else {
                                return $value;
                                }
                        }
                    }
                } else if(count($match)>1 && $match[1] == 'a'){
                    if (isset($args[0])) {
                        $value = array_shift($args);
                        if (is_array($value)) {
                                if (empty($value)) {
                                    throw new Exception('Empty identifier list');
                                }
                                $i = 0; $v= "";
                                foreach($value as $key => $val){
                                    if($i>0)
                                        $v = $v . ", ";
                                    if(isset($val))
                                        if(is_string($val))
                                            $v = $v . "`{$key}` = '$val'";
                                        else
                                            $v = $v . "`{$key}` = $val";
                                    else
                                        $v = $v . "`{$key}` = NULL";
                                    $i++;
                                } 
                                $value = $v;
                                return $value;
                        } else {
                            return $value;
                        }
                    
                    }                    
                }
            }
        }, $query);
        
        if (!empty($args)) {
            throw new Exception('too many parameters');
        } 
        return $query;
    }
    
    public function skip()
    {
        return false;
    }
}
