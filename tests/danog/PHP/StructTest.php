<?php
function iter_integer_formats($byteorders = ['', '@', '=', '<', '>', '!']) {
   foreach (['b', 'B', 'h', 'H', 'i', 'I', 'l', 'L', 'q', 'Q', 'n', 'N'] as $code) {
        foreach ($byteorders as $byteorder) {
            if (in_array($byteorder, ['', '@']) && in_array($code, ['q', 'Q']) && !($this->HAVE_LONG_LONG)) {
                continue;
            }
            if (!in_array($byteorder, ['', '@']) && in_array($code, ['n', 'N'])) {
                continue;
            }
            yield([$code, $byteorder]);
        }
    }
}
$doingphptests = true;
/*
* @expectedException \danog\PHP\StructException
*/
class StructTest extends PHPUnit\Framework\TestCase
{
    function __construct() {
        parent::__construct();
        $this->struct = new \danog\PHP\StructClass();
        $this->integer_codes = ['b', 'B', 'h', 'H', 'i', 'I', 'l', 'L', 'q', 'Q', 'n', 'N'];
        $this->byteorders = ['', '@', '=', '<', '>', '!'];
//        $this->setExpectedException('\danog\PHP\StructException');
        try {
            if($this->struct->pack('q', 5)) $this->HAVE_LONG_LONG = true;
        }
        catch(StructException $e) {
            $this->HAVE_LONG_LONG = false;
        }
    }
    function bigendian_to_native($value) {
        if ($this->struct->BIG_ENDIAN) {
            return $value;
        }
        return strrev($value);
    }


    function test_consistence() {
        $this->setExpectedException('\danog\PHP\StructException');
        $this->expectException($this->struct->calcsize('Z'));
        $sz = $this->struct->calcsize('i');
        $this->assertEquals(($sz * 3), $this->struct->calcsize('iii'));
        $fmt = 'cbxxxxxxhhhhiillffd?';
        $fmt3 = '3c3b18x12h6i6l6f3d3?';
        $sz = $this->struct->calcsize($fmt);
        $sz3 = $this->struct->calcsize($fmt3);
        $this->assertEquals(($sz * 3), $sz3);
        $this->expectException($this->struct->pack('iii', 3));
        $this->expectException($this->struct->pack('i', 3, 3, 3));
        $this->expectException($this->struct->pack('i', 'foo'));
        $this->expectException($this->struct->pack('P', 'foo'));
        $this->expectException($this->struct->unpack('d', 'flap'));
        $s = $this->struct->pack('ii', 1, 2);
        $this->expectException($this->struct->unpack('iii', $s));
        $this->expectException($this->struct->unpack('i', $s));
    }
    function test_transitiveness() {
        $c = 'a';
        $b = 1;
        $h = 255;
        $i = 65535;
        $l = 65536;
        $f = 3.1415;
        $d = 3.1415;
        $t = true;
        foreach (['', '@', '<', '>', '=', '!'] as $prefix) {
            foreach (['xcbhilfd?', 'xcBHILfd?'] as $format) {
                $format = $prefix . $format;
                $s = $this->struct->pack($format, $c, $b, $h, $i, $l, $f, $d, $t);
                list($cp, $bp, $hp, $ip, $lp, $fp, $dp, $tp) = $this->struct->unpack($format, $s);
                $this->assertEquals($cp, $c);
                $this->assertEquals($bp, $b);
                $this->assertEquals($hp, $h);
                $this->assertEquals($ip, $i);
                $this->assertEquals($lp, $l);
                $this->assertEquals((int)(100 * $fp), (int)(100 * $f));
                $this->assertEquals((int)(100 * $dp), (int)(100 * $d));
                $this->assertEquals($tp, $t);
            }
        }
    }
    function test_new_features() {
        $tests = [['c', 'a', 'a', 'a', 0], ['xc', 'a', "\0a", "\0a", 0], ['cx', 'a', "a\0", "a\0", 0], ['s', 'a', 'a', 'a', 0], ['0s', 'helloworld', '', '', 1], ['1s', 'helloworld', 'h', 'h', 1], ['9s', 'helloworld', 'helloworl', 'helloworl', 1], ['10s', 'helloworld', 'helloworld', 'helloworld', 0], ['11s', 'helloworld', "helloworld\0", "helloworld\0", 1], ['20s', 'helloworld', 'helloworld' . pack("@10"), 'helloworld' . pack("@10"), 1], ['b', 7, '', '', 0], ['b', -7, hex2bin('f9'), hex2bin('f9'), 0], ['B', 7, '', '', 0], ['B', 249, hex2bin('f9'), hex2bin('f9'), 0], ['h', 700, hex2bin('02bc'), hex2bin('bc02'), 0], ['h', -700, hex2bin('fd44'), hex2bin('44fd'), 0], ['H', 700, hex2bin('02bc'), hex2bin('bc02'), 0], ['H', (65536 - 700), hex2bin('fd44'), hex2bin('44fd'), 0], ['i', 70000000, hex2bin('042c1d80'), hex2bin('801d2c04'), 0], ['i', -70000000, hex2bin('fbd3e280'), hex2bin('80e2d3fb'), 0], ['I', 70000000, hex2bin('042c1d80'), hex2bin('801d2c04'), 0], ['I', (4294967296 - 70000000), hex2bin('fbd3e280'), hex2bin('80e2d3fb'), 0], ['l', 70000000, hex2bin('042c1d80'), hex2bin('801d2c04'), 0], ['l', -70000000, hex2bin('fbd3e280'), hex2bin('80e2d3fb'), 0], ['L', 70000000, hex2bin('042c1d80'), hex2bin('801d2c04'), 0], ['L', (4294967296 - 70000000), hex2bin('fbd3e280'), hex2bin('80e2d3fb'), 0], ['f', 2.0, hex2bin('40000000'), hex2bin('00000040'), 0], ['d', 2.0, hex2bin('4000000000000000'), hex2bin('0000000000000040'), 0], ['f', -2.0, hex2bin('c0000000'), hex2bin('000000c0'), 0], ['d', -2.0, hex2bin('c000000000000000'), hex2bin('00000000000000c0'), 0], ['?', 0, "\0", "\0", 0], ['?', 3, chr(1), chr(1), 1], ['?', true, chr(1), chr(1), 0], ['?', [], "\0", "\0", 1], ['?', [1], chr(1), chr(1), 1]];
        foreach ($tests as list($fmt, $arg, $big, $lil, $asy)) {
            foreach (
                [
                    ['>' . $fmt, $big],
                    ['!' . $fmt, $big], 
                    ['<' . $fmt, $lil], 
                    ['=' . $fmt, $this->struct->struct->BIG_ENDIAN ? $big : $lil]
                ] as list($xfmt, $exp)
            ) {
                $res = $this->struct->pack($xfmt, $arg);
                if($res != $exp) {
                    var_dump(bin2hex($res), bin2hex($exp), $xfmt, $arg);
                }
                $this->assertEquals($exp, $res);
                
                $this->assertEquals($this->struct->calcsize($xfmt), strlen($res));

                $rev = $this->struct->unpack($xfmt, $res) [0];
                if (($rev != $arg)) {
                    $this->assertTrue((bool) $asy);
                }
            }
        }
    }
    function test_calcsize() {
        $expected_size = ['b' => 1, 'B' => 1, 'h' => 2, 'H' => 2, 'i' => 4, 'I' => 4, 'l' => 4, 'L' => 4, 'q' => 8, 'Q' => 8];
        foreach (iter_integer_formats(['=', '<', '>', '!']) as list($code, $byteorder)) {
            $format = $byteorder . $code;
            $size = $this->struct->calcsize($format);
            if($size != $expected_size[$code]) {
                var_dump($code, $size, $expected_size[$code], $byteorder);
            }
            $this->assertEquals($size, $expected_size[$code]);
        }
        $native_pairs = ['bB', 'hH', 'iI', 'lL', 'nN'];
        if ($this->HAVE_LONG_LONG) {
            $native_pairs[] = 'qQ';
        }
        foreach ($native_pairs as $format_pair) {
            foreach (['', '@'] as $byteorder) {
                $signed_size = $this->struct->calcsize($byteorder . $format_pair[0]);
                $unsigned_size = $this->struct->calcsize($byteorder . $format_pair[1]);
                $this->assertEquals($signed_size, $unsigned_size);
            }
        }
        $this->assertEquals(1, $this->struct->calcsize('b'));
        $this->assertLessThanOrEqual($this->struct->calcsize('h'), 2);
        $this->assertLessThanOrEqual($this->struct->calcsize('l'), 4);
        $this->assertLessThanOrEqual($this->struct->calcsize('i'), $this->struct->calcsize('h'));
        $this->assertLessThanOrEqual($this->struct->calcsize('l'), $this->struct->calcsize('i'));
        if ($this->HAVE_LONG_LONG) {
            $this->assertLessThanOrEqual($this->struct->calcsize('q'), 8);
            $this->assertLessThanOrEqual($this->struct->calcsize('q'), $this->struct->calcsize('l'));
        }
        $this->assertGreaterThanOrEqual($this->struct->calcsize('i'), $this->struct->calcsize('n'));
        $this->assertGreaterThanOrEqual($this->struct->calcsize('P'), $this->struct->calcsize('n'));
    }
    function test_isbigendian() {
        $this->assertEquals((bin2hex($this->struct->pack('=i', 1)[0]) == "00"), $this->struct->struct->BIG_ENDIAN);
    }
}

/*


    function test_integers() {
         class IntTester extends TestCase {
            function __construct($format) {
                py2php_kwargs_method_call('super($IntTester, $this)', '__init__', [], ["methodName" => 'test_one']);
                 $this->format = $format;
                 $this->code = $format[-1];
                 $this->byteorder = array_slice($format, null, -1);
                 if (!(in_array($this->byteorder, $this->byteorders))) {
                     throw new $ValueError(sprintf('unrecognized packing byteorder: %s', $this->byteorder));
                 }
                 $this->bytesize = $this->struct->calcsize($format);
                    $this->bitsize = ($this->bytesize * 8);
                    if (in_array($this->code, tuple('bhilqn'))) {
                        $this->signed = true;
                        $this->min_value = - pow(2, ($this->bitsize - 1));
                        $this->max_value = (pow(2, ($this->bitsize - 1)) - 1);
                    } else if (in_array($this->code, tuple('BHILQN'))) {
                        $this->signed = false;
                        $this->min_value = 0;
                        $this->max_value = (pow(2, $this->bitsize) - 1);
                    } else {
                        throw new $ValueError(sprintf('unrecognized format code: %s', $this->code));
                    }
                }
                function test_one($x, $pack = $this->struct->pack, $unpack = $this->struct->unpack, $unhexlify = $binascii->unhexlify) {
                    $format = $this->format;
                    if (($this->min_value <= $x) && ($x <= $this->max_value)) {
                        $expected = $x;
                        if ($this->signed && ($x < 0)) {
                            $expected+= 1 << $this->bitsize;
                        }
                        $this->assertGreaterThanOrEqual($expected, 0);
                        $expected = sprintf('%x', $expected);
                        if (strlen($expected) & 1) {
                            $expected = '0' . $expected;
                        }
                        $expected = $expected->encode('ascii');
                        $expected = $unhexlify($expected);
                        $expected = ((' ' * ($this->bytesize - strlen($expected))) + $expected);
                        if (($this->byteorder == '<') || in_array($this->byteorder, ['', '@', '=']) && !($this->struct->BIG_ENDIAN)) {
                            $expected = strrev($expected);
                        }
                        $this->assertEquals(strlen($expected), $this->bytesize);
                        $got = $this->pack($format, $x);
                        $this->assertEquals($got, $expected);
                        $retrieved = $unpack($format, $got) [0];
                        $this->assertEquals($x, $retrieved);
                        $this->expectException([$this->struct->error, $TypeError], $unpack, $format, '' . $got);
                    } else {
                        $this->expectException([$OverflowError, $ValueError, $this->struct->error], $this->pack, $format, $x);
                    }
                }
                function run() {
                    require_once ('random.php');
                    $values = [];
                    foreach (pyjslib_range(($this->bitsize + 3)) as $exp) {
                        $values[] = 1 << $exp;
                    }
                    foreach (pyjslib_range($this->bitsize) as $i) {
                        $val = 0;
                        foreach (pyjslib_range($this->bytesize) as $j) {
                            $val = $val << 8 | new randrange(256);
                        }
                        $values[] = $val;
                    }
                    $values->extend([300, 700000, (sys::maxsize * 4) ]);
                    foreach ($values as $base) {
                        foreach ([-$base, $base] as $val) {
                            foreach ([-1, 0, 1] as $incr) {
                                $x = ($val + $incr);
                                $this->test_one($x);
                            }
                        }
                    }
                    class NotAnInt {
                        function __int__() {
                            return 42;
                        }
                    };
                    class Indexable extends object {
                        function __construct($value) {
                            $this->_value = $value;
                        }
                        function __index__() {
                            return $this->_value;
                        }
                    };
                    class BadIndex extends object {
                        function __index__() {
                            throw new TypeError;
                        }
                        function __int__() {
                            return 42;
                        }
                    };
                    $this->expectException($this->struct->pack($this->format, 'a string'));
                    $this->expectException($this->struct->pack($this->struct->pack, $this->format, randrange));
                    //$this->expectException($this->struct->pack($this->struct->pack, $this->format, 3 + 42j));
                    $this->expectException($this->struct->pack($this->struct->pack, $this->format, $NotAnInt()));
                    $this->expectException($this->struct->pack($this->struct->pack, $this->format, $BadIndex()));
                    foreach ([$Indexable(0), $Indexable(10), $Indexable(17), $Indexable(42), $Indexable(100), $Indexable(127) ] as $obj) {
                        try {
                            $this->struct->pack($format, $obj);
                        }
                        catch(StructException $e) {
                            $this->fail('integer code pack failed on object with \'__index__\' method');
                        }
                    }
                    foreach ([$Indexable('a'), $Indexable('b'), $Indexable(null), $Indexable(['a' => 1]), $Indexable([1, 2, 3]) ] as $obj) {
                        $this->expectException([$TypeError, $this->struct->error], $this->struct->pack, $this->format, $obj);
                    }
                }
            };
            foreach (iter_integer_formats() as list($code, $byteorder)) {
                $format = ($byteorder + $code);
                $t = $IntTester($format);
                $t->run();
            }
        }
        function test_nN_code() {
            function assertStructError($func, $args, ...$kwargs) {
                // py2php.fixme "with" unsupported.
                $this->assertIn('bad char in struct format', pyjslib_str($cm->exception));
            }
            foreach ('nN' as $code) {
                foreach (['=', '<', '>', '!'] as $byteorder) {
                    $format = ($byteorder + $code);
                    $assertStructError($this->struct->calcsize, $format);
                    $assertStructError($this->struct->pack, $format, 0);
                    $assertStructError($this->struct->unpack, $format, '');
                }
            }
        }
        function test_p_code() {
            foreach ([['p', 'abc', ' ', ''], ['1p', 'abc', ' ', ''], ['2p', 'abc', 'a', 'a'], ['3p', 'abc', 'ab', 'ab'], ['4p', 'abc', 'abc', 'abc'], ['5p', 'abc', 'abc ', 'abc'], ['6p', 'abc', 'abc  ', 'abc'], ['1000p', ('x' * 1000), 'ÿ' . ('x' * 999), ('x' * 255) ]] as list($code, $input, $expected, $expectedback)) {
                $got = $this->struct->pack($code, $input);
                $this->assertEquals($got, $expected);
                list($got) = $this->struct->unpack($code, $got);
                $this->assertEquals($got, $expectedback);
            }
        }
        function test_705836() {
            require_once ('math.php');
            foreach (pyjslib_range(1, 33) as $base) {
                $delta = 0.5;
                while ((($base - ($delta / 2.0)) != $base)) {
                    $delta/= 2.0;
                }
                $smaller = ($base - $delta);
                $this->packed = $this->struct->pack('<f', $smaller);
                $unpacked = $this->struct->unpack('<f', $this->packed) [0];
                $this->assertEquals($base, $unpacked);
                $bigpacked = $this->struct->pack('>f', $smaller);
                $this->assertEquals($bigpacked, strrev($this->packed));
                $unpacked = $this->struct->unpack('>f', $bigpacked) [0];
                $this->assertEquals($base, $unpacked);
            }
            $big = (1 << 24 - 1);
            $big = $math->ldexp($big, (127 - 23));
            $this->packed = $this->struct->pack('>f', $big);
            $unpacked = $this->struct->unpack('>f', $this->packed) [0];
            $this->assertEquals($big, $unpacked);
            $big = (1 << 25 - 1);
            $big = $math->ldexp($big, (127 - 24));
            $this->expectException($OverflowError, $this->struct->pack, '>f', $big);
        }
        function test_1530559() {
            foreach (iter_integer_formats() as list($code, $byteorder)) {
                $format = ($byteorder + $code);
                $this->expectException($this->struct->error, $this->struct->pack, $format, 1.0);
                $this->expectException($this->struct->error, $this->struct->pack, $format, 1.5);
            }
            $this->expectException($this->struct->error, $this->struct->pack, 'P', 1.0);
            $this->expectException($this->struct->error, $this->struct->pack, 'P', 1.5);
        }
        function test_unpack_from() {
            $test_string = 'abcd01234';
            $fmt = '4s';
            $s = $this->struct->Struct($fmt);
            foreach ([$bytes, $bytearray] as $cls) {
                $data = $cls($test_string);
                $this->assertEquals($s->unpack_from($data), ['abcd']);
                $this->assertEquals($s->unpack_from($data, 2), ['cd01']);
                $this->assertEquals($s->unpack_from($data, 4), ['0123']);
                foreach (pyjslib_range(6) as $i) {
                    $this->assertEquals($s->unpack_from($data, $i), [array_slice($data, $i, ($i + 4) - $i) ]);
                }
                foreach (pyjslib_range(6, (strlen($test_string) + 1)) as $i) {
                    $this->expectException($this->struct->error, $s->unpack_from, $data, $i);
                }
            }
            foreach ([$bytes, $bytearray] as $cls) {
                $data = $cls($test_string);
                $this->assertEquals($this->struct->unpack_from($fmt, $data), ['abcd']);
                $this->assertEquals($this->struct->unpack_from($fmt, $data, 2), ['cd01']);
                $this->assertEquals($this->struct->unpack_from($fmt, $data, 4), ['0123']);
                foreach (pyjslib_range(6) as $i) {
                    $this->assertEquals($this->struct->unpack_from($fmt, $data, $i), [array_slice($data, $i, ($i + 4) - $i) ]);
                }
                foreach (pyjslib_range(6, (strlen($test_string) + 1)) as $i) {
                    $this->expectException($this->struct->error, $this->struct->unpack_from, $fmt, $data, $i);
                }
            }
        }
        function test_unpack_with_buffer() {
            $data1 = array ::array('B', '4Vx');
            $data2 = memoryview('4Vx');
            foreach ([$data1, $data2] as $data) {
                list($value) = $this->struct->unpack('>I', $data);
                $this->assertEquals($value, 305419896);
            }
        }
        function test_bool() {
            class ExplodingBool extends object {
                function __bool__() {
                    throw new OSError;
                }
            };
            foreach ((tuple('<>!=') + ['']) as $prefix) {
                $false = [[], [], [], '', 0];
                $true = [[1], 'test', 5, -1, (4294967295 + 1), (4294967295 / 2) ];
                $falseFormat = ($prefix + ('?' * strlen($false)));
                $this->packedFalse = $this->struct->pack($falseFormat, ...$false);
                $unpackedFalse = $this->struct->unpack($falseFormat, $this->packedFalse);
                $trueFormat = ($prefix + ('?' * strlen($true)));
                $this->packedTrue = $this->struct->pack($trueFormat, ...$true);
                $unpackedTrue = $this->struct->unpack($trueFormat, $this->packedTrue);
                $this->assertEquals(strlen($true), strlen($unpackedTrue));
                $this->assertEquals(strlen($false), strlen($unpackedFalse));
                foreach ($unpackedFalse as $t) {
                    $this->assertFalse($t);
                }
                foreach ($unpackedTrue as $t) {
                    $this->assertTrue($t);
                }
                $this->packed = $this->struct->pack($prefix . '?', 1);
                $this->assertEquals(strlen($this->packed), $this->struct->calcsize($prefix . '?'));
                if ((strlen($this->packed) != 1)) {
                    py2php_kwargs_method_call($this, 'assertFalse', [$prefix], ["msg" => sprintf('encoded bool is not one byte: %r', $this->packed) ]);
                }
                try {
                    $this->struct->pack($prefix . '?', $ExplodingBool());
                }
                catch(OSError $e) {
                }
                //            py2php: else block not supported in PHP.
                //            else {
                //                $this->fail(sprintf('Expected OSError: struct.pack(%r, ExplodingBool())', $prefix . '?'));
                //            }
                
            }
            foreach (['', '', 'ÿ', '', 'ð'] as $c) {
                $this->assertTrue($this->struct->unpack('>?', $c) [0]);
            }
        }
        function test_count_overflow() {
            $hugecount = '{}b'->format((sys::maxsize + 1));
            $this->expectException($this->struct->error, $this->struct->calcsize, $hugecount);
            $hugecount2 = '{}b{}H'->format((sys::maxsize / 2), (sys::maxsize / 2));
            $this->expectException($this->struct->error, $this->struct->calcsize, $hugecount2);
        }
        function test_trailing_counter() {
            $store = array ::array('b', (' ' * 100));
            $this->expectException($this->struct->error, $this->struct->pack, '12345');
            $this->expectException($this->struct->error, $this->struct->unpack, '12345', '');
            $this->expectException($this->struct->error, $this->struct->unpack_from, '12345', $store, 0);
            $this->expectException($this->struct->error, $this->struct->pack, 'c12345', 'x');
            $this->expectException($this->struct->error, $this->struct->unpack, 'c12345', 'x');
            $this->expectException($this->struct->error, $this->struct->unpack_from, 'c12345', $store, 0);
            $this->expectException($this->struct->error, $this->struct->pack, '14s42', 'spam and eggs');
            $this->expectException($this->struct->error, $this->struct->unpack, '14s42', 'spam and eggs');
            $this->expectException($this->struct->error, $this->struct->unpack_from, '14s42', $store, 0);
        }
        function test_Struct_reinitialization() {
            $s = $this->struct->Struct('i');
            $s->__construct('ii');
        }
        function check_sizeof($format_str, $number_of_codes) {
            $totalsize = support::calcobjsize('2n3P');
            $totalsize+= ($this->struct->calcsize('P3n0P') * ($number_of_codes + 1));
            support::check_sizeof($this, $this->struct->Struct($format_str), $totalsize);
        }
        function test__sizeof__() {
            foreach ($this->integer_codes as $code) {
                $this->check_sizeof($code, 1);
            }
            $this->check_sizeof('BHILfdspP', 9);
            $this->check_sizeof(('B' * 1234), 1234);
            $this->check_sizeof('fd', 2);
            $this->check_sizeof('xxxxxxxxxxxxxx', 0);
            $this->check_sizeof('100H', 1);
            $this->check_sizeof('187s', 1);
            $this->check_sizeof('20p', 1);
            $this->check_sizeof('0s', 1);
            $this->check_sizeof('0c', 0);
        }
    }*/
