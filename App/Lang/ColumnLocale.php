<?php

namespace App\Lang;

class ColumnLocale {

    const columnLocale = [
        'kod' => 'Код',
        'naimenovanie' => 'Наименование',
        'artikul' => 'Артикул',
        'ed_izm' => 'Единица измерения',
        'ves' => 'Вес',
        'obem' => 'Объем',
        'material' => 'Материал',
        'roliki' => 'Ролики',
        'pyatiluche' => 'Пятилучье',
        'podlokotniki' => 'Подлокотники',
        'mehanizm_kachaniya' => 'Механизм качания',
        'regulirovka_vysoty' => 'Регулировка высоты',
        'shirina_sideniya' => 'Ширина сидения',
        'glubina_sidenya' => 'Глубина сидения',
        'vysota_spinki' => 'Высота спинки',
        'diapazon_regulirovki' => 'Диапазон регулировки',
        'gaz_patron' => 'Газовый патрон',
        'dopustimaya_nagruzka' => 'Допустимая нагрузка',
        'rama' => 'Рама',
        'krestovina' => 'Крестовина',
        'shirina_upakovki' => 'Ширина упаковки',
        'vysota_upakovki' => 'Высота упаковки',
        'glubina_upakovki' => 'Глубина упаковки',
        'izdeliy_v_upakovke' => 'Изделий в упаковке',
        'razmer' => 'Размер',
        'cvet' => 'Цвет',
        'kod_s_sayta' => 'Код с сайта',
        'ssylka_url' => 'Ссылка',
        'kolichestvo_mest' => 'Количество мест',
        'cena1' => 'Цена 1',
        'cena2' => 'Цена 2',
        'edinica_izmereniya' => 'Единица измерения',
        'ssylkaurl' => 'Ссылка'
    ];

    public static function handle($columns): array {
        $localized = [];

        foreach ($columns as $column) {
            if (isset(self::columnLocale[$column])) {
                $localized[] = self::columnLocale[$column];
                continue;
            }

            $localized[] = $column;
        }

        return $localized;
    }

    public static function transtale($column) : string|int|false {
        return array_search($column, self::columnLocale);
    }

}