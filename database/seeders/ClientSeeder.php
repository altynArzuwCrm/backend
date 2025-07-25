<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [ 'name' => 'Мария', 'company_name' => 'Дальнобойщик', 'contacts' => [ ['type' => 'phone', 'value' => '862778255'] ] ],
            [ 'name' => 'Тойли', 'company_name' => 'Еди Доган Edi Dogan', 'contacts' => [ ['type' => 'phone', 'value' => '861006611'] ] ],
            [ 'name' => 'Бегли', 'company_name' => 'кафе Довлетли Döwletli kafe', 'contacts' => [ ['type' => 'phone', 'value' => '861554545'] ] ],
            [ 'name' => 'Гадам', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '864692770'], ['type' => 'email', 'value' => 'ali.yklymow@gmail.com'] ] ],
            [ 'name' => 'Мурат', 'company_name' => 'Алемгошар Älemgoşar', 'contacts' => [ ['type' => 'phone', 'value' => '864012239'], ['type' => 'phone', 'value' => '970169'], ['type' => 'email', 'value' => 'mekan857@gmail.com'] ] ],
            [ 'name' => 'Эмиль', 'company_name' => 'Салкын Отаг Salkyn Otag', 'contacts' => [ ['type' => 'phone', 'value' => '864058555'] ] ],
            [ 'name' => 'Артур', 'company_name' => 'Салкын Отаг Salkyn Otag', 'contacts' => [ ['type' => 'phone', 'value' => '865859290'] ] ],
            [ 'name' => 'Анжела', 'company_name' => 'Дил Мач', 'contacts' => [ ['type' => 'phone', 'value' => '864180818'] ] ],
            [ 'name' => 'Максуд', 'company_name' => 'Сапалы Заман Sapaly Zaman', 'contacts' => [ ['type' => 'phone', 'value' => '865701967'], ['type' => 'email', 'value' => 'maksud.77sm@icloud.com'] ] ],
            [ 'name' => 'Ольга Попова', 'company_name' => 'Olii', 'contacts' => [ ['type' => 'phone', 'value' => '865675841'] ] ],
            [ 'name' => 'Джерена', 'company_name' => 'Мелек Безелер Melek Bezeler', 'contacts' => [ ['type' => 'phone', 'value' => '863447632'], ['type' => 'email', 'value' => 'dzerendzeren78@gmail.com'] ] ],
            [ 'name' => 'Карлен муж Каталины', 'company_name' => 'мебель', 'contacts' => [ ['type' => 'phone', 'value' => '862673331'] ] ],
            [ 'name' => 'Бегли', 'company_name' => 'Рысгалы Тяджир Rysgaly Tajir, Гадымы Мермер Gadymy Mermer', 'contacts' => [ ['type' => 'phone', 'value' => '865353545'], ['type' => 'email', 'value' => 'begliilekov@mail.ru'] ] ],
            [ 'name' => 'Саята', 'company_name' => 'Фо ю For you', 'contacts' => [ ['type' => 'phone', 'value' => '865026981'] ] ],
            [ 'name' => 'Яран', 'company_name' => 'Монти Monty', 'contacts' => [ ['type' => 'phone', 'value' => '865850466'] ] ],
            [ 'name' => 'Арслан', 'company_name' => 'Хайсенс Hisense', 'contacts' => [ ['type' => 'phone', 'value' => '864072487'] ] ],
            [ 'name' => 'Даянч', 'company_name' => 'Айпери маталар Aýperi matalar', 'contacts' => [ ['type' => 'phone', 'value' => '862030843'] ] ],
            [ 'name' => 'Агаджан', 'company_name' => 'Йолла биз Ýolla Biz', 'contacts' => [ ['type' => 'phone', 'value' => '862773723'] ] ],
            [ 'name' => 'Наталья', 'company_name' => 'НИЦ', 'contacts' => [ ['type' => 'phone', 'value' => '865015874'] ] ],
            [ 'name' => 'Бахар', 'company_name' => 'Мерал кафе Meral kafe', 'contacts' => [ ['type' => 'phone', 'value' => '864996292'], ['type' => 'email', 'value' => 'yusupbrdyw@gmail.com'] ] ],
            [ 'name' => 'Нуры', 'company_name' => 'Семендер Semender', 'contacts' => [ ['type' => 'phone', 'value' => '864765666'], ['type' => 'email', 'value' => 'gurbanguliyevnury@gmail.com'] ] ],
            [ 'name' => 'Эджегуль', 'company_name' => 'Айбике Aýbike', 'contacts' => [ ['type' => 'phone', 'value' => '862246538'] ] ],
            [ 'name' => 'Лейли', 'company_name' => 'Крамбл Kramble', 'contacts' => [ ['type' => 'phone', 'value' => '871566990'] ] ],
            [ 'name' => 'Сельби', 'company_name' => 'Туркмен терджиме Türkmen terjime', 'contacts' => [ ['type' => 'phone', 'value' => '941978'] ] ],
            [ 'name' => 'Джерена', 'company_name' => 'Мельхем донер Melhem donner', 'contacts' => [ ['type' => 'phone', 'value' => '871108010'] ] ],
            [ 'name' => 'Батыр', 'company_name' => 'Сенагат банк Senagat bank', 'contacts' => [ ['type' => 'phone', 'value' => '865716899'] ] ],
            [ 'name' => 'Абдурахим', 'company_name' => 'Мускус Атырлар Muskus Atyrlar', 'contacts' => [ ['type' => 'phone', 'value' => '865672187'] ] ],
            [ 'name' => 'Зарина', 'company_name' => 'Фаммели Fammeli', 'contacts' => [ ['type' => 'phone', 'value' => '862643352'] ] ],
            [ 'name' => 'Бегенч', 'company_name' => 'Мастер клей Master Glue', 'contacts' => [ ['type' => 'phone', 'value' => '709119'] ] ],
            [ 'name' => 'Джерена', 'company_name' => 'Алтын Босага Altyn Bosaga', 'contacts' => [ ['type' => 'phone', 'value' => '861179933'] ] ],
            [ 'name' => 'Мерджена', 'company_name' => 'Ак Йол Ak Ýol', 'contacts' => [ ['type' => 'phone', 'value' => '863136816'] ] ],
            [ 'name' => 'Ширина', 'company_name' => 'Аэстет Aestet', 'contacts' => [ ['type' => 'phone', 'value' => '864311672'] ] ],
            [ 'name' => 'Бяшим', 'company_name' => 'стиральная машина инструкция', 'contacts' => [ ['type' => 'phone', 'value' => '864031415'] ] ],
            [ 'name' => 'Вадим', 'company_name' => 'Парфюм тм Parfum tm', 'contacts' => [ ['type' => 'phone', 'value' => '865855621'] ] ],
            [ 'name' => 'Айгозель', 'company_name' => 'Пригласительные', 'contacts' => [ ['type' => 'phone', 'value' => '864801080'] ] ],
            [ 'name' => 'Азат', 'company_name' => 'Бьюти Beauty', 'contacts' => ['type' => 'phone', 'value' => '863015501'] ],
            [ 'name' => 'Джейхун', 'company_name' => 'Джиовани Giovane', 'contacts' => [ ['type' => 'phone', 'value' => '718248'], ['type' => 'email', 'value' => 'jeyson307073@mail.ru'] ] ],
            [ 'name' => 'Гунча', 'company_name' => 'Джиовани Giovane', 'contacts' => [ ['type' => 'phone', 'value' => '862061956'] ] ],
            [ 'name' => 'Мария', 'company_name' => 'Доставка из америки', 'contacts' => [ ['type' => 'phone', 'value' => '862439324'] ] ],
            [ 'name' => 'Ширина', 'company_name' => 'Мерьем Шоп Merýem Shop', 'contacts' => [ ['type' => 'phone', 'value' => '862135553'] ] ],
            [ 'name' => 'Ахмед', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '865639450'] ] ],
            [ 'name' => 'Кумуш', 'company_name' => 'Орлан Orlan', 'contacts' => [ ['type' => 'phone', 'value' => '863105921'] ] ],
            [ 'name' => 'Гульзада', 'company_name' => 'Зада Zada', 'contacts' => [ ['type' => 'phone', 'value' => '605090'] ] ],
            [ 'name' => 'Энеджан', 'company_name' => 'магазин Квин Queen', 'contacts' => [ ['type' => 'phone', 'value' => '863253555'] ] ],
            [ 'name' => null, 'company_name' => 'Мир 7 базар', 'contacts' => [ ['type' => 'phone', 'value' => '861322295'] ] ],
            [ 'name' => 'Рашид', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '861704747'] ] ],
            [ 'name' => 'Сельби', 'company_name' => 'Мирка  Трейд Mirka Trade', 'contacts' => [ ['type' => 'phone', 'value' => '864051854'], ['type' => 'email', 'value' => 'selbi.muradova@mirkatrade.com'] ] ],
            [ 'name' => 'Айна', 'company_name' => 'Хорджун Horjun', 'contacts' => [ ['type' => 'phone', 'value' => '865715786'] ] ],
            [ 'name' => 'Новруз', 'company_name' => 'Мега Копетдаг Mega Kopetdag', 'contacts' => [ ['type' => 'phone', 'value' => '863638433'] ] ],
            [ 'name' => null, 'company_name' => 'Донской кофе', 'contacts' => [ ['type' => 'phone', 'value' => '864003752'] ] ],
            [ 'name' => 'Мердан', 'company_name' => 'Ак Япрак Ak Ýaprak', 'contacts' => [ ['type' => 'phone', 'value' => '862990255'] ] ],
            [ 'name' => 'Перхат', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '863109066'] ] ],
            [ 'name' => 'Саида', 'company_name' => 'КьюИДи QED', 'contacts' => [ ['type' => 'phone', 'value' => '865355851'], ['type' => 'email', 'value' => 'srovshenova@q2impact.com'] ] ],
            [ 'name' => 'Байрам', 'company_name' => 'Алем травел Älem travel', 'contacts' => [ ['type' => 'phone', 'value' => '863743493'] ] ],
            [ 'name' => 'Мая', 'company_name' => 'Алем травел Älem travel', 'contacts' => [ ['type' => 'phone', 'value' => '865858755'] ] ],
            [ 'name' => 'Эзиз', 'company_name' => 'Алем травел Älem travel', 'contacts' => [ ] ],
            [ 'name' => 'Сердар', 'company_name' => 'Гоша бокс Goşa Box', 'contacts' => [ ['type' => 'phone', 'value' => '863777667'] ] ],
            [ 'name' => 'Азат', 'company_name' => 'Акыллы Ой  Умный дом Akylly öý', 'contacts' => [ ['type' => 'phone', 'value' => '865466554'] ] ],
            [ 'name' => 'Мухаммед', 'company_name' => 'Сарай паб Saraý Pub', 'contacts' => [ ['type' => 'phone', 'value' => '862941771'], ['type' => 'other', 'value' => 'телеграм'] ] ],
            [ 'name' => 'Нязли', 'company_name' => 'Голуби', 'contacts' => [ ['type' => 'phone', 'value' => '861787261'] ] ],
            [ 'name' => null, 'company_name' => 'Менгли Meňgli', 'contacts' => [ ['type' => 'phone', 'value' => '862928866'] ] ],
            [ 'name' => 'Селия', 'company_name' => 'Хелло стор Hello Store', 'contacts' => [ ['type' => 'phone', 'value' => '862827722'], ['type' => 'phone', 'value' => '863614646'], ['type' => 'other', 'value' => 'инстаграмм'] ] ],
            [ 'name' => 'Нариман', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '863267899'] ] ],
            [ 'name' => 'Энеша', 'company_name' => 'Фаберлик Faberlic', 'contacts' => [ ['type' => 'phone', 'value' => '863773394'] ] ],
            [ 'name' => 'Гульнара', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '636139'] ] ],
            [ 'name' => 'Оля', 'company_name' => 'Мавы Умман Mawy Umman', 'contacts' => [ ['type' => 'phone', 'value' => '871650199'], ['type' => 'phone', 'value' => '865160536'] ] ],
            [ 'name' => null, 'company_name' => 'Макам мебель Mäkäm mebel', 'contacts' => [ ['type' => 'phone', 'value' => '861785378'] ] ],
            [ 'name' => 'Довлет', 'company_name' => 'Энза заден Enza', 'contacts' => [ ['type' => 'phone', 'value' => '865119929'] ] ],
            [ 'name' => null, 'company_name' => 'Барлы кофе Barly kofe', 'contacts' => [ ['type' => 'phone', 'value' => '86405106'] ] ],
            [ 'name' => 'Аллаш', 'company_name' => 'Эйдж хоум Edge home', 'contacts' => [ ['type' => 'phone', 'value' => '871120525'] ] ],
            [ 'name' => 'Энеша', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '862504041'] ] ],
            [ 'name' => 'Мерет', 'company_name' => 'Желато Jelato', 'contacts' => [ ['type' => 'phone', 'value' => '863777794'] ] ],
            [ 'name' => 'Кристина', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '864371818'] ] ],
            [ 'name' => 'Виктория', 'company_name' => 'Электротехно хызмат Мпауэр Elektrotehno hyzmat Mpower', 'contacts' => [ ['type' => 'phone', 'value' => '865699346'], ['type' => 'other', 'value' => 'инстаграмм'] ] ],
            [ 'name' => 'Юлия', 'company_name' => 'Ди борн Di born', 'contacts' => [ ['type' => 'phone', 'value' => '862363574'], ['type' => 'email', 'value' => 'webppalexey@gmail.com'], ['type' => 'email', 'value' => 'yuliapewnewa@gmail.com'] ] ],
            [ 'name' => 'Лейла', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '862041822'] ] ],
            [ 'name' => 'Рустам', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '863030511'] ] ],
            [ 'name' => 'Аман', 'company_name' => 'Хекем Hekem', 'contacts' => [ ['type' => 'phone', 'value' => '865879929'] ] ],
            [ 'name' => 'Дженнета', 'company_name' => 'Буиг Bouygues', 'contacts' => [ ['type' => 'phone', 'value' => '865058211'], ['type' => 'email', 'value' => 'je.kutliyeva@bouygues-construction.com'], ['type' => 'other', 'value' => 'kutliyeva, jennet'] ] ],
            [ 'name' => 'Аймурад', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '864350206'] ] ],
            [ 'name' => 'Ыхлас', 'company_name' => null, 'contacts' => [ ['type' => 'phone', 'value' => '865610310'] ] ],
            [ 'name' => 'Гарабатыр', 'company_name' => 'Кенек', 'contacts' => [ ['type' => 'phone', 'value' => '865505296'] ] ],
            [ 'name' => 'Мухаммет Али', 'company_name' => 'Земин', 'contacts' => [ ['type' => 'phone', 'value' => '865726127'], ['type' => 'phone', 'value' => '865718042'] ] ],
            [ 'name' => 'Аслан', 'company_name' => 'Борец', 'contacts' => [ ['type' => 'phone', 'value' => '861226496'] ] ],
            [ 'name' => 'Мекан', 'company_name' => 'Борец', 'contacts' => [ ['type' => 'phone', 'value' => '861594870'] ] ],
            [ 'name' => 'Гозель', 'company_name' => 'Диор Dior', 'contacts' => [ ['type' => 'phone', 'value' => '865642475'] ] ],
            [ 'name' => 'Безирген', 'company_name' => 'Керем парфюм Kerem parfume', 'contacts' => [ ['type' => 'phone', 'value' => '865166678'] ] ],
            [ 'name' => 'Юсуп', 'company_name' => 'Версаче Студио Versage studio', 'contacts' => [ ['type' => 'phone', 'value' => '865602257'] ] ],
            [ 'name' => 'Ровшен', 'company_name' => 'Аяз баба Aýaz baba', 'contacts' => [ ['type' => 'phone', 'value' => '865683848'] ] ],
            [ 'name' => 'Аркадий', 'company_name' => 'магазин запчастей', 'contacts' => [ ['type' => 'phone', 'value' => '865815224'] ] ],
            [ 'name' => 'Умыт', 'company_name' => 'Топары Topary', 'contacts' => [ ['type' => 'phone', 'value' => '865550317'] ] ],
            [ 'name' => 'Эзиз', 'company_name' => 'Туркмен газ Türkmen gaz', 'contacts' => [ ['type' => 'phone', 'value' => '865507605'] ] ],
            [ 'name' => 'Бахара', 'company_name' => 'Юпек йол халы Ýupek ýol haly', 'contacts' => [ ['type' => 'phone', 'value' => '865593033'] ] ],
        ];

        foreach ($clients as $clientData) {
            $contacts = $clientData['contacts'] ?? [];
            // Если contacts — не массив массивов, а один контакт, обернуть его в массив
            if (isset($contacts['type'])) {
                $contacts = [$contacts];
            }
            unset($clientData['contacts']);
            // Если name пустой/null, подставить company_name
            if (empty($clientData['name'])) {
                $clientData['name'] = $clientData['company_name'] ?? 'Без имени';
            }
            $client = \App\Models\Client::create($clientData);
            foreach ($contacts as $contact) {
                // Заменить mail_name на other
                if (isset($contact['type']) && $contact['type'] === 'mail_name') {
                    $contact['type'] = 'other';
                }
                $contact['client_id'] = $client->id;
                \App\Models\ClientContact::create($contact);
            }
        }
    }
}
