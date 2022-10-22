<?php

namespace App\Configs;

enum SQLVariableTypes {

    /**
     * Тип, на который будут заменены все колонки с типом null.
     * Используется, если все ячейки колонки в документе пустые, следовательно, тип определить невозможно.
     */
    const NULL_REPLACEMENT = 'TEXT';
    const VARCHAR = 'VARCHAR(255)';

    case TEXT;
    case INT;
    case FLOAT;
    case BOOLEAN;
    case NULL;

}