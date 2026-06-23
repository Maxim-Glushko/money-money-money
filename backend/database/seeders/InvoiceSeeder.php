<?php

namespace Database\Seeders;

use App\Models\Invoice;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['ТОВ «Альфа Постач»',      '12345678'],
            ['ФОП Бондаренко І.В.',      '87654321'],
            ['ТОВ «Бета Технології»',    '11223344'],
            ['ПП «Гамма Сервіс»',        '55667788'],
            ['ТОВ «Дельта Трейд»',       '22334455'],
            ['ФОП Коваленко О.П.',       '33445566'],
            ['ТОВ «Епсілон Логістик»',   '44556677'],
            ['ПАТ «Зета Груп»',          '55668877'],
            ['ТОВ «Ета Консалтинг»',     '66778899'],
            ['ФОП Мельник В.С.',         '77889900'],
            ['ТОВ «Тета Буд»',           '88990011'],
            ['ПП «Йота Медіа»',          '99001122'],
            ['ТОВ «Каппа Девелопмент»',  '10203040'],
            ['ФОП Шевченко Т.Г.',        '20304050'],
            ['ТОВ «Лямбда Ресурс»',      '30405060'],
            ['ПАТ «Мю Індастріз»',       '40506070'],
            ['ТОВ «Ню Агро»',            '50607080'],
            ['ФОП Петренко А.М.',        '60708090'],
            ['ТОВ «Омікрон Фінанс»',     '70809001'],
            ['ПП «Пі Сістемс»',          '80900112'],
        ];

        $statuses = ['pending', 'approved', 'rejected'];

        for ($i = 1; $i <= 50; $i++) {
            [$name, $taxId] = $suppliers[($i - 1) % count($suppliers)];
            $status   = $statuses[($i - 1) % count($statuses)];
            $net      = round(mt_rand(500, 50000) / 100 * 100, 2);
            $vat      = round($net * 0.2, 2);
            $gross    = round($net + $vat, 2);
            $issueDay = str_pad(mt_rand(1, 20), 2, '0', STR_PAD_LEFT);
            $issueMonth = str_pad(mt_rand(1, 6), 2, '0', STR_PAD_LEFT);
            $dueDay   = str_pad(mt_rand(21, 28), 2, '0', STR_PAD_LEFT);
            $dueMonth = str_pad(mt_rand(7, 12), 2, '0', STR_PAD_LEFT);

            Invoice::create([
                'number'          => sprintf('INV-2026-%03d', $i),
                'supplier_name'   => $name,
                'supplier_tax_id' => $taxId,
                'net_amount'      => $net,
                'vat_amount'      => $vat,
                'gross_amount'    => $gross,
                'currency'        => 'UAH',
                'status'          => $status,
                'issue_date'      => "2026-{$issueMonth}-{$issueDay}",
                'due_date'        => "2026-{$dueMonth}-{$dueDay}",
            ]);
        }
    }
}
