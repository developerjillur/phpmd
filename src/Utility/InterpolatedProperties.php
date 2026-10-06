<?php

/**
 * This file is part of PHP Mess Detector.
 *
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 *
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license https://opensource.org/licenses/bsd-license.php BSD License
 * @link http://phpmd.org/
 */

namespace PHPMD\Utility;

use PDepend\Source\AST\ASTHeredoc;
use PDepend\Source\AST\ASTLiteral;
use PDepend\Source\AST\ASTNode;
use PDepend\Source\AST\ASTString;
use PDepend\Source\AST\ASTVariable;

/**
 * The properties read on `$this` with the simple string interpolation syntax,
 * such as "$this->name" or "$this?->name".
 *
 * PDepend does not build a property postfix for that syntax: the string holds
 * the variable `$this`, a literal `->` (or `?->`) and a literal starting with the
 * property name. The braced syntax "{$this->name}" is parsed as a property postfix and is
 * not covered here.
 *
 * @internal
 */
final class InterpolatedProperties
{
    /**
     * @return list<string> Property names with the `$` prefix, such as `$name`.
     */
    public static function readOnThis(ASTNode $node): array
    {
        $names = [];

        foreach ([ASTString::class, ASTHeredoc::class] as $type) {
            foreach ($node->findChildrenOfType($type) as $string) {
                $names = [...$names, ...self::readInString($string)];
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private static function readInString(ASTNode $string): array
    {
        $names = [];
        $children = $string->getChildren();

        foreach ($children as $index => $child) {
            if (!$child instanceof ASTVariable || strcasecmp($child->getImage(), '$this') !== 0) {
                continue;
            }

            $arrow = $children[$index + 1] ?? null;
            $name = $children[$index + 2] ?? null;

            if (
                $arrow instanceof ASTLiteral &&
                in_array($arrow->getImage(), ['->', '?->'], true) &&
                $name instanceof ASTLiteral &&
                preg_match('(^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)', $name->getImage(), $match)
            ) {
                $names[] = '$' . $match[0];
            }
        }

        return $names;
    }
}
