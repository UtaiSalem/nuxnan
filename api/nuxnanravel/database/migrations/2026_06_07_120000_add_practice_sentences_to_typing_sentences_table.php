<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SOURCE = 'practice_200_sentences';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('typing_sentences')) {
            return;
        }

        $now = now();
        $sentences = $this->sentences();

        $chunks = array_chunk($sentences, 50);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $sentence) {
                // Calculate rough word count
                $wordCount = $sentence['language'] === 'en'
                    ? str_word_count($sentence['text'])
                    : max(1, (int)(mb_strlen($sentence['text']) / 4)); // Rough estimate for Thai

                DB::table('typing_sentences')->updateOrInsert(
                    [
                        'language' => $sentence['language'],
                        'difficulty' => $sentence['difficulty'],
                        'text' => $sentence['text'],
                    ],
                    [
                        'word_count' => $wordCount,
                        'source' => self::SOURCE,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('typing_sentences')) {
            return;
        }

        DB::table('typing_sentences')->where('source', self::SOURCE)->delete();
    }

    private function sentences(): array
    {
        return [
            // ==========================================
            // THAI SENTENCES (100)
            // ==========================================

            // Beginner (25)
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'นกบินบนฟ้า'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'แมวกินปลาทู'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'หมาวิ่งในสวน'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ฉันชอบกินข้าว'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ทะเลสีฟ้าใส'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ท้องฟ้าสวยมาก'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ดาวเต็มฟ้าเลย'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ต้นไม้สีเขียว'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ดอกไม้บานแล้ว'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ลมพัดเย็นสบาย'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'เราไปเที่ยวกัน'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ฝนกำลังจะตก'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'กางร่มด้วยนะ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'วันนี้อากาศดี'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ดื่มน้ำเยอะๆ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'อย่านอนดึกนะ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ตื่นเช้าสดใส'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ยิ้มรับวันใหม่'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'กินผักผลไม้'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'รักษาสุขภาพ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ล้างมือบ่อยๆ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ใส่หน้ากากนะ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ขับรถดีๆล่ะ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ถึงบ้านยังคะ'],
            ['language' => 'th', 'difficulty' => 'beginner', 'text' => 'ฝันดีราตรีสวัสดิ์'],

            // Easy (25)
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'การเดินทางทำให้เราได้เรียนรู้สิ่งใหม่'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ความพยายามอยู่ที่ไหน ความสำเร็จอยู่ที่นั่น'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'เวลาไม่เคยรอใคร จงทำวันนี้ให้ดีที่สุด'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'หนังสือหนึ่งเล่มสามารถเปิดโลกใบใหม่ได้'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'รอยยิ้มคือเครื่องสำอางที่สวยที่สุดบนใบหน้า'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ครอบครัวคือที่พักพิงใจในวันที่เราเหนื่อยล้า'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'อาหารมื้อนี้อร่อยมาก ฉันชอบมันจริงๆ'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'การออกกำลังกายช่วยให้ร่างกายแข็งแรง'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ดนตรีเป็นภาษาสากลที่ทุกคนเข้าใจได้'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'เพื่อนแท้คือคนที่อยู่เคียงข้างเราเสมอ'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'แสงแดดยามเช้าช่วยให้รู้สึกกระปรี้กระเปร่า'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ธรรมชาติมักจะมอบความสงบให้กับจิตใจเสมอ'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'กาแฟแก้วโปรดทำให้เช้าวันนี้สมบูรณ์แบบ'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'การออมเงินตั้งแต่วันนี้ดีต่ออนาคต'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'การคิดบวกช่วยให้เราก้าวข้ามอุปสรรคได้'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ความเมตตาเป็นสิ่งที่เราควรมีให้แก่กัน'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'สุนัขเป็นเพื่อนที่ซื่อสัตย์ที่สุดของมนุษย์'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'แมวเป็นสัตว์ที่รักความอิสระและสันโดษ'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'การให้คือการได้รับที่มีความสุขที่สุด'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'เป้าหมายที่ชัดเจนคือจุดเริ่มต้นของความสำเร็จ'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'อย่ากลัวที่จะเริ่มต้นใหม่ เพราะมันคือโอกาส'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ความผิดพลาดคือครูที่ดีที่สุดในชีวิต'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ความสุขเกิดขึ้นได้จากสิ่งเล็กๆ รอบตัว'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'จงภูมิใจในตัวเองและสิ่งที่คุณทำอยู่เสมอ'],
            ['language' => 'th', 'difficulty' => 'easy', 'text' => 'ชีวิตคือการผจญภัยที่เต็มไปด้วยสีสัน'],

            // Normal (25)
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'เทคโนโลยีในปัจจุบันก้าวหน้าไปอย่างรวดเร็วจนตามแทบไม่ทัน'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การเปลี่ยนแปลงสภาพภูมิอากาศเป็นปัญหาที่ทั่วโลกต้องร่วมกันแก้ไข'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การสื่อสารที่มีประสิทธิภาพคือหัวใจสำคัญของการทำงานเป็นทีม'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'เศรษฐกิจในยุคดิจิทัลขับเคลื่อนด้วยข้อมูลและนวัตกรรมใหม่ๆ'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การวางแผนทางการเงินอย่างรอบคอบจะช่วยลดความเสี่ยงในอนาคตได้'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การรักษาสมดุลระหว่างชีวิตและการทำงานเป็นสิ่งที่ทุกคนปรารถนา'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ปัญญาประดิษฐ์กำลังเข้ามามีบทบาทในหลากหลายอุตสาหกรรมทั่วโลก'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การศึกษาคือรากฐานที่สำคัญในการพัฒนาทรัพยากรมนุษย์ของประเทศ'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ความหลากหลายทางวัฒนธรรมทำให้สังคมของเรามีความน่าสนใจมากยิ่งขึ้น'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การทำงานที่บ้านกลายเป็นเรื่องปกติใหม่หลังจากเกิดโรคระบาดใหญ่'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ความรับผิดชอบต่อสังคมเป็นสิ่งที่องค์กรสมัยใหม่ควรให้ความสำคัญ'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ความยั่งยืนด้านสิ่งแวดล้อมเริ่มต้นจากการตระหนักรู้ของพวกเราทุกคน'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'โซเชียลมีเดียมีทั้งข้อดีและข้อเสีย ขึ้นอยู่กับว่าเราจะเลือกใช้มันอย่างไร'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การเคารพสิทธิและเสรีภาพของผู้อื่นเป็นพื้นฐานของประชาธิปไตย'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ภาวะผู้นำไม่ใช่อำนาจ แต่คือการสร้างแรงบันดาลใจให้ผู้อื่นทำในสิ่งที่ดี'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'สุขภาพจิตมีความสำคัญไม่แพ้สุขภาพกาย เราควรดูแลใส่ใจมันให้ดี'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การเรียนรู้ตลอดชีวิตเป็นกุญแจสู่การพัฒนาตนเองอย่างยั่งยืนในยุคนี้'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ความเครียดจากการทำงานสามารถส่งผลกระทบต่อสุขภาพระยะยาวได้'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ศิลปะและวัฒนธรรมเป็นสิ่งที่สะท้อนให้เห็นถึงตัวตนของคนในชาตินั้น'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'พลังงานสะอาดเป็นทางเลือกใหม่ที่จะช่วยลดมลพิษในอากาศของเรา'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'อุตสาหกรรมการท่องเที่ยวมีความสำคัญต่อการฟื้นฟูเศรษฐกิจของประเทศ'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การลงทุนในความรู้คือการลงทุนที่ให้ผลตอบแทนคุ้มค่าที่สุดเสมอ'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ความคิดสร้างสรรค์มักจะเกิดขึ้นเมื่อเราอนุญาตให้ตัวเองได้คิดนอกกรอบ'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'การให้อภัยผู้อื่นคือการปลดปล่อยตัวเองจากความทุกข์ที่เกาะกินจิตใจ'],
            ['language' => 'th', 'difficulty' => 'normal', 'text' => 'ทุกคนมีสิทธิ์ที่จะมีความฝันและพยายามทำมันให้กลายเป็นความจริง'],

            // Hard (25)
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ปรากฏการณ์เรือนกระจกที่รุนแรงขึ้นส่งผลให้ระดับน้ำทะเลเพิ่มสูงขึ้นอย่างมีนัยสำคัญ'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'สถาปัตยกรรมไมโครเซอร์วิสช่วยให้การปรับขนาดระบบซอฟต์แวร์มีความยืดหยุ่นและคล่องตัวสูง'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'นโยบายการเงินแบบผ่อนคลายถูกนำมาใช้เพื่อกระตุ้นเศรษฐกิจในช่วงที่เกิดสภาวะชะลอตัว'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ทฤษฎีสัมพัทธภาพของอัลเบิร์ต ไอน์สไตน์ พลิกโฉมความเข้าใจเกี่ยวกับปริภูมิและเวลาของมนุษยชาติ'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'พันธุกรรมและพฤติกรรมการใช้ชีวิตล้วนเป็นปัจจัยเสี่ยงที่ก่อให้เกิดโรคไม่ติดต่อเรื้อรัง'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'วิทยาการข้อมูลเชิงลึกเป็นสาขาวิชาที่รวบรวมทักษะด้านสถิติและคณิตศาสตร์เข้าด้วยกันอย่างลงตัว'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ระบบนิเวศน์ทางธรรมชาติมีความซับซ้อนและพึ่งพาอาศัยกันและกันในลักษณะของห่วงโซ่อาหาร'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'การรักษาความปลอดภัยทางไซเบอร์กลายเป็นวาระแห่งชาติเนื่องจากภัยคุกคามรูปแบบใหม่ที่ทวีความรุนแรง'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'อัลกอริทึมการเรียนรู้ของเครื่องจำเป็นต้องอาศัยข้อมูลปริมาณมหาศาลเพื่อเพิ่มความแม่นยำในการวิเคราะห์'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'เศรษฐศาสตร์พฤติกรรมพยายามอธิบายว่าเหตุใดมนุษย์จึงตัดสินใจอย่างไม่สมเหตุสมผลในบางสถานการณ์'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ความตกลงปารีสว่าด้วยการเปลี่ยนแปลงสภาพภูมิอากาศเป็นก้าวสำคัญในความร่วมมือระดับนานาชาติ'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'การเข้ารหัสข้อมูลแบบปลายทางถึงปลายทางช่วยรับประกันว่าข้อมูลจะถูกเปิดเผยให้แค่ผู้รับเท่านั้น'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'คอมพิวเตอร์ควอนตัมมีศักยภาพในการแก้ปัญหาที่ซับซ้อนซึ่งคอมพิวเตอร์แบบดั้งเดิมไม่สามารถทำได้'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'นิติปรัชญาตั้งคำถามถึงสาระสำคัญของกฎหมายและความสัมพันธ์ระหว่างกฎหมายกับศีลธรรมอันดีงาม'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'การกลายพันธุ์ของไวรัสเป็นปรากฏการณ์ตามธรรมชาติที่เกิดขึ้นเมื่อมีการแพร่เชื้ออย่างรวดเร็วและกว้างขวาง'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ปรัชญาอัตถิภาวนิยมเน้นย้ำถึงเสรีภาพในการเลือกและความรับผิดชอบส่วนบุคคลที่มีต่อการตัดสินใจนั้น'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'การจัดการห่วงโซ่อุปทานที่มีประสิทธิภาพต้องอาศัยการประสานงานและการติดตามข้อมูลแบบเรียลไทม์'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'โครงข่ายประสาทเทียมถูกออกแบบมาโดยได้แรงบันดาลใจจากโครงสร้างการทำงานของสมองมนุษย์'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'กฎหมายลิขสิทธิ์มีจุดประสงค์เพื่อปกป้องทรัพย์สินทางปัญญาและส่งเสริมการสร้างสรรค์ผลงานใหม่'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'การวิเคราะห์เชิงทำนายสามารถช่วยคาดการณ์แนวโน้มของตลาดล่วงหน้าได้อย่างมีประสิทธิภาพ'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'อุดมการณ์เสรีนิยมใหม่ให้ความสำคัญกับการค้าเสรีและการลดบทบาทแทรกแซงของภาครัฐลง'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'การวิจัยทางคลินิกต้องผ่านกระบวนการตรวจสอบทางจริยธรรมเพื่อคุ้มครองความปลอดภัยของผู้เข้าร่วม'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'ปฏิสัมพันธ์ระหว่างแรงโน้มถ่วงและหลุมดำยังคงเป็นปริศนาทางฟิสิกส์ดาราศาสตร์ที่รอการค้นพบ'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'นวัตกรรมแบบพลิกผันเปลี่ยนรูปแบบอุตสาหกรรมเดิมไปอย่างสิ้นเชิงด้วยโมเดลธุรกิจที่แตกต่าง'],
            ['language' => 'th', 'difficulty' => 'hard', 'text' => 'เทคโนโลยีบล็อกเชนช่วยขจัดความจำเป็นในการใช้ตัวกลางสำหรับการทำธุรกรรมดิจิทัลที่น่าเชื่อถือ'],


            // ==========================================
            // ENGLISH SENTENCES (100)
            // ==========================================

            // Beginner (25)
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'The cat is sleeping.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'A dog runs fast.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'I love to read.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'She has a red bag.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'The sun is hot.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'He plays the piano.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'We go to school.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'They like ice cream.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'My car is blue.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'Look at the bird.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'It is a good day.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'I drink pure water.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'She is my friend.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'He sits on a chair.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'The book is open.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'A boy kicks a ball.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'We walk to the park.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'They swim in the pool.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'I have two pens.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'She wears a hat.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'He cooks dinner.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'The apple is sweet.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'We watch a movie.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'The baby is crying.'],
            ['language' => 'en', 'difficulty' => 'beginner', 'text' => 'It is time to sleep.'],

            // Easy (25)
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'The quick brown fox jumps over the lazy dog.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Practice makes perfect if you keep trying every day.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'A journey of a thousand miles begins with a single step.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Good things come to those who wait and work hard.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Reading a book is like traveling to another world.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Always remember to smile and be kind to others.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Learning to type faster can save you a lot of time.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Do not give up, the beginning is always the hardest.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Water is essential for all living beings on our planet.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Music has the power to heal the mind and soul.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Taking a short walk can help clear your mind completely.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Eating healthy food is very important for your body.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'The sky is so blue and the clouds are white today.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Every mistake is a chance to learn something new.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'You are stronger than you think you are.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Family and friends make life beautiful and worth living.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Do what you love and you will never work a day.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Keep your face always toward the sunshine.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'A positive attitude brings positive results.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Success is the sum of small efforts repeated daily.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Take a deep breath and relax your shoulders.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Tomorrow is a new day with new opportunities.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Believe in yourself and all that you can be.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Focus on your goals and do not look back.'],
            ['language' => 'en', 'difficulty' => 'easy', 'text' => 'Happiness is a choice you make every single morning.'],

            // Normal (25)
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Technology has dramatically transformed the way we communicate with each other globally.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Artificial intelligence is rapidly advancing and changing the landscape of many industries.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Maintaining a healthy work-life balance is crucial for your long-term mental well-being.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Climate change is one of the most pressing issues that humanity is facing today.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Effective leadership is about inspiring others to achieve their full potential and greatness.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Education is the most powerful weapon which you can use to change the world.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Financial literacy empowers individuals to make better decisions regarding their future investments.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Renewable energy sources are essential to reduce carbon emissions and protect our environment.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Cultural diversity enriches our society by bringing different perspectives and unique ideas together.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Remote work has become a permanent reality for many companies after the global pandemic.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Regular exercise not only improves physical health but also enhances cognitive brain function.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Social media can be a powerful tool for connection, but it also has its downsides.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Continuous learning is necessary to adapt to the fast-paced changes in the modern workplace.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Emotional intelligence is often considered more important than IQ in determining professional success.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Creativity involves breaking out of established patterns in order to look at things differently.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Good communication skills are essential for resolving conflicts and building strong relationships.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Innovation is what distinguishes a leader from a follower in any competitive business environment.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Volunteering in your community gives you a sense of purpose and helps those in need.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Time management allows you to accomplish more in a shorter period of time with less effort.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'The internet has made information instantly accessible to almost anyone around the globe.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Sustainable practices must be adopted by corporations to ensure a greener and cleaner future.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Overcoming adversity builds character and resilience that prepares you for future challenges.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Collaboration and teamwork are fundamental to executing complex projects successfully.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'An optimistic mindset helps individuals cope better with stress and unexpected difficulties.'],
            ['language' => 'en', 'difficulty' => 'normal', 'text' => 'Reading diverse literature fosters empathy and a deeper understanding of human nature.'],

            // Hard (25)
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The dichotomy between existential dread and the pursuit of meaning often characterizes modern philosophical discourse.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Quantum computing leverages the principles of superposition and entanglement to perform calculations exponentially faster.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'End-to-end encryption ensures that cryptographic keys are known only to the communicating parties, preventing eavesdropping.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The psychological phenomenon of cognitive dissonance occurs when individuals hold contradictory beliefs or values simultaneously.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Microservices architecture facilitates the decentralized deployment of robust, highly scalable, and fault-tolerant software applications.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Neuroplasticity refers to the brain\'s remarkable ability to reorganize itself by forming new neural connections throughout life.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'A fundamental principle of macroeconomics is that inflation and unemployment often have an inverse relationship in the short term.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The integration of sophisticated machine learning algorithms necessitates massive, meticulously curated datasets to minimize algorithmic bias.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Blockchain technology provides a decentralized and immutable ledger, which mitigates the need for an authoritative intermediary entity.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Epistemology investigates the origins, nature, methods, and limits of human knowledge and justified belief.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Astrophysicists hypothesize that supermassive black holes reside at the gravitational centers of most, if not all, massive galaxies.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The principle of comparative advantage dictates that countries should specialize in producing goods with the lowest opportunity cost.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Asynchronous programming paradigms allow computing systems to handle concurrent operations without obstructing the primary execution thread.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Linguistic determinism suggests that the grammatical structure of a language heavily influences its speakers\' worldview and cognition.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The rapid proliferation of ubiquitous computing devices has exponentially increased the attack surface for malicious cyber threat actors.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'CRISPR-Cas9 is a groundbreaking gene-editing technology that allows scientists to precisely alter DNA sequences and modify gene function.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Anthropogenic greenhouse gas emissions are the primary catalyst exacerbating the unprecedented acceleration of contemporary climate change.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Behavioral economics bridges the gap between psychology and economics by analyzing how emotional factors influence financial decisions.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The placebo effect demonstrates the astonishing power of the human mind to elicit tangible physiological improvements based purely on expectation.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Polymorphism in object-oriented programming allows entities of different types to be treated as instances of the same overarching class.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Geopolitical tensions frequently precipitate volatility in global financial markets, impacting international supply chains and commodity prices.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The uncanny valley hypothesis posits that humanoid objects appearing almost, but not exactly, like real human beings elicit feelings of eeriness.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Thermodynamics dictates that the total entropy of an isolated system can never decrease over time, defining the irreversible arrow of time.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'Jurisprudence explores the theoretical and philosophical underpinnings of legal systems and their consequential societal implications.'],
            ['language' => 'en', 'difficulty' => 'hard', 'text' => 'The intricacies of natural language processing require intricate algorithms to disambiguate contextual nuances and idiomatic colloquialisms.'],
        ];
    }
};
