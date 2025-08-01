<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Custom DQL function for PostgreSQL JSONB_EXTRACT_TEXT function
 * Usage: JSONB_EXTRACT_TEXT(field, path)
 */
class JsonbExtractText extends FunctionNode
{
    private $field;
    private $path;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'JSONB_EXTRACT_TEXT(%s, %s)',
            $this->field->dispatch($sqlWalker),
            $this->path->dispatch($sqlWalker)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        
        $this->field = $parser->ArithmeticPrimary();
        
        $parser->match(Lexer::T_COMMA);
        
        $this->path = $parser->ArithmeticPrimary();
        
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}