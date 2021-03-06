<?php

namespace pcrov\JsonReader\Parser;

use pcrov\JsonReader\JsonReader;

class ParserTest extends \PHPUnit_Framework_TestCase
{

    /** @var Tokenizer */
    protected $tokenizer;

    /** @dataProvider provideTestParser */
    public function testParser($input, $expected)
    {
        $tokenizer = $this->tokenizer;
        $tokenizer->setTokens($input);

        $parser = new \IteratorIterator(new Parser($tokenizer));
        $parser->rewind();
        $this->assertTrue($parser->valid());

        $iterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ANY | \MultipleIterator::MIT_KEYS_ASSOC);
        $iterator->attachIterator($parser, "parser");
        $iterator->attachIterator(new \ArrayIterator($expected), "expected");

        foreach ($iterator as $tuple) {
            $this->assertSame($tuple["expected"], $tuple["parser"]);
        }
    }

    /** @dataProvider provideTestParserError */
    public function testParserError($input, $expectedMessage)
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedMessage);

        $tokenizer = $this->tokenizer;
        $tokenizer->setTokens($input);

        $parser = new \IteratorIterator(new Parser($tokenizer));
        $parser->rewind();

        foreach ($parser as $_);
    }

    public function provideTestParser()
    {
        return [
            [
                [
                    [Tokenizer::T_STRING, "foo"],
                ],
                [
                    [JsonReader::STRING, null, "foo", 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_NUMBER, 42],
                ],
                [
                    [JsonReader::NUMBER, null, 42, 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_TRUE, true],
                ],
                [
                    [JsonReader::BOOL, null, true, 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_FALSE, false],
                ],
                [
                    [JsonReader::BOOL, null, false, 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_NULL, null],
                ],
                [
                    [JsonReader::NULL, null, null, 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_BEGIN_ARRAY, null],
                    [Tokenizer::T_END_ARRAY, null],
                ],
                [
                    [JsonReader::ARRAY, null, null, 0],
                    [JsonReader::END_ARRAY, null, null, 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_BEGIN_ARRAY, null],
                    [Tokenizer::T_FALSE, false],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_BEGIN_ARRAY, null],
                    [Tokenizer::T_END_ARRAY, null],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_NUMBER, 42],
                    [Tokenizer::T_END_ARRAY, null],
                ],
                [
                    [JsonReader::ARRAY, null, null, 0],
                    [JsonReader::BOOL, null, false, 1],
                    [JsonReader::ARRAY, null, null, 1],
                    [JsonReader::END_ARRAY, null, null, 1],
                    [JsonReader::NUMBER, null, 42, 1],
                    [JsonReader::END_ARRAY, null, null, 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_END_OBJECT, null],
                ],
                [
                    [JsonReader::OBJECT, null, null, 0],
                    [JsonReader::END_OBJECT, null, null, 0],
                ]
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "foo"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_FALSE, false],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_STRING, "bar"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_BEGIN_ARRAY, null],
                    [Tokenizer::T_END_ARRAY, null],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_STRING, "answer"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_NUMBER, 42],
                    [Tokenizer::T_END_OBJECT, null],
                ],
                [
                    [JsonReader::OBJECT, null, null, 0],
                    [JsonReader::BOOL, "foo", false, 1],
                    [JsonReader::ARRAY, "bar", null, 1],
                    [JsonReader::END_ARRAY, "bar", null, 1],
                    [JsonReader::NUMBER, "answer", 42, 1],
                    [JsonReader::END_OBJECT, null, null, 0],
                ]
            ],
        ];
    }

    public function provideTestParserError()
    {
        return [
            [
                [

                ],
                "Line 42: Unexpected end of file."
            ],
            [
                [
                    [Tokenizer::T_TRUE, true],
                    [Tokenizer::T_NULL, null],
                ],
                "Line 42: Unexpected token T_NULL."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_ARRAY, null],
                ],
                "Line 42: Unexpected end of file."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_ARRAY, null],
                    [Tokenizer::T_NULL, null],
                    [Tokenizer::T_TRUE, true],
                ],
                "Line 42: Unexpected token T_TRUE."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                ],
                "Line 42: Unexpected end of file."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_FALSE, false],
                ],
                "Line 42: Unexpected token T_FALSE."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_STRING, "err"],
                ],
                "Line 42: Unexpected token T_STRING."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_COLON, null],
                ],
                "Line 42: Unexpected token T_COLON."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_STRING, "value"],
                    [Tokenizer::T_COMMA, null],
                ],
                "Line 42: Unexpected end of file."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_STRING, "value"],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_END_OBJECT, null],
                ],
                "Line 42: Unexpected token T_END_OBJECT."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_STRING, "value"],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_END_OBJECT, null],
                ],
                "Line 42: Unexpected token T_COMMA."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_STRING, "value"],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_STRING, "value"],
                    [Tokenizer::T_END_OBJECT, null],
                ],
                "Line 42: Unexpected token T_COMMA."
            ],
            [
                [
                    [Tokenizer::T_BEGIN_OBJECT, null],
                    [Tokenizer::T_STRING, "name"],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_STRING, "value"],
                    [Tokenizer::T_COMMA, null],
                    [Tokenizer::T_FALSE, false],
                    [Tokenizer::T_COLON, null],
                    [Tokenizer::T_STRING, "value"],
                    [Tokenizer::T_END_OBJECT, null],
                ],
                "Line 42: Unexpected token T_FALSE."
            ],
        ];
    }

    protected function setUp()
    {
        $this->tokenizer = new class() implements \IteratorAggregate, Tokenizer
        {

            /** @var array */
            private $tokens = [];

            public function setTokens(array $tokens)
            {
                $this->tokens = $tokens;
            }

            public function getIterator(): \Generator
            {
                foreach ($this->tokens as $token) {
                    yield $token[0] => $token[1];
                }
            }

            public function getLineNumber(): int
            {
                return 42;
            }
        };
    }
}
