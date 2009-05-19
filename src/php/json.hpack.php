<?php

/** json.hpack for PHP (4/5)
 * @description JSON Homogeneous Collection Packer
 * @version     1.0.1
 * @author      Andrea Giammarchi
 * @license     Mit Style License
 * @project     http://github.com/WebReflection/json.hpack/tree/master
 * @blog        http://webreflection.blogspot.com/
 */

/** json_hpack(homogeneousCollection:Array[, compression:Number]):Array
 * @param   Array       mono dimensional homogeneous collection of objects to pack
 * @param   [Number]    optional compression level from 0 to 4 - default 0
 * @return  Array       optimized collection
 */
function json_hpack($collection, $compression = 0){
    if(3 < $compression){
        $i      = json_hbest($collection);
        $result = json_hbest($i);
    } else {
        $header = array();
        $result = array(&$header);
        $first  = $collection[0];
        $k      = 0;
        foreach($first as $key => $value)
            $header[] = $key;
        $len = count($header);
        for($length = count($collection), $i = 0; $i < $length; ++$i){
            for(
                $item   = $collection[$i],
                $row    = array(),
                $j      = 0;
                $j < $len; ++$j
            )
                $row[$j] = $item->$header[$j];
            $result[] = $row;
        }
        $index  = count($result);
        if(0 < $compression){
            for($row = $result[1], $j = 0; $j < $len; ++$j){
                if(!is_float($row[$j]) && !is_int($row[$j])){
                    $first = array();
                    $header[$j] = array($header[$j], &$first);
                    for($i = 1; $i < $index; ++$i){
                        $value  = $result[$i][$j];
                        $l      = array_search($value, $first, true);
                        $result[$i][$j] = $l === false ? array_push($first, $value) - 1 : $l;
                    }
                    unset($first);
                }
            }
        }
        if(2 < $compression){
            for($j = 0; $j < $len; ++$j){
                if(is_array($header[$j])){
                    for($row = $header[$j][1], $value = array(), $first = array(), $k = 0, $i = 1; $i < $index; ++$i){
                        $value[$k] = $row[$first[$k] = $result[$i][$j]];
                        ++$k;
                    }
                    if(strlen(json_encode($value)) < strlen(json_encode(array_merge($first, $row)))){
                        for($k = 0, $i = 1; $i < $index; ++$i){
                            $result[$i][$j] = $value[$k];
                            ++$k;
                        }
                        $header[$j] = $header[$j][0];
                    }
                }
            }
        }
        elseif(1 < $compression){
            $length -= floor($length / 2);
            for($j = 0; $j < $len; ++$j){
                if(is_array($header[$j])){
                    if($length < count($first = $header[$j][1])){
                        for($i = 1; $i < $index; ++$i){
                            $value = $result[$i][$j];
                            $result[$i][$j] = $first[$value];
                        }
                        $header[$j] = $header[$j][0];
                    }
                }
            }
        }
        if(0 < $compression){
            for($j = 0; $j < $len; ++$j){
                if(is_array($header[$j])){
                    $enum = $header[$j][1];
                    $header[$j] = $header[$j][0];
                    array_splice($header, $j + 1, 0, array($enum));
                    ++$len;
                    ++$j;
                }
            }
        }
    }
    return $result;
}

/** json_hunpack(packedCollection:Array):Array
 * @param   Array       optimized collection to unpack
 * @return  Array       original  mono dimensional homogeneous collection of objects
 */
function json_hunpack($collection){
    for(
        $result = array(),
        $keys   = array(),
        $header = $collection[0],
        $len    = count($header),
        $length = count($collection),
        $i      = 0,
        $k      = 0,
        $l      = 0;
        $i < $len; ++$i
    ){
        $keys[] = $header[$i];
        $k = $i + 1;
        if($k < $len && is_array($header[$k])){
            ++$i;
            for($j = 1; $j < $length; ++$j){
                $row = &$collection[$j];
                $row[$l] = $header[$i][$row[$l]];
            };
        };
        ++$l;
    };
    for($j = 1; $j < $length; ++$j)
        $result[] = json_hunpack_createRow($keys, $collection[$j]);
    return $result;
}

/** json_hunpack_createRow(objectKeys:Array, values:Array):stdClass
 * @param   Array       a list of keys to assign
 * @param   Array       a list of values to assign
 * @return  stdClass    object representing the row
 */
function json_hunpack_createRow($keys, $array){
    $o = new StdClass;
    for($i = 0, $len = count($keys); $i < $len; ++$i)
        $o->$keys[$i] = $array[$i];
    return $o;
}

/** json_hbest(packedCollection:Array):Number
 * @param   Array       optimized collection to clone
 * @return  Number      best compression option
 */
function json_hbest($collection){
    static  $_cache = array();
    if(is_array($collection)){
        for($i       = 0,
            $j       = 0,
            $len     = 0,
            $length  = 0;
            $i < 4;
            ++$i
        ){
            $_cache[$i] = json_hpack($collection, $i);
            $len = strlen(json_encode($_cache[$i]));
            if($length === 0)
                $length = $len;
            elseif($len < $length){
                $length = $len;
                $j = $i;
            }
        }
        return $j;
    } else {
        $result = $_cache[$collection];
        $_cache = array();
        return $result;
    }
}

?>