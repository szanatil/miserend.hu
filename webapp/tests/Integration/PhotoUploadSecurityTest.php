<?php

use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

/**
 * #709: kép helyett feltöltött .php fájl a közvetlen URL-jén LEFUTOTT.
 *
 * A lánc három hibából állt:
 *   1. a típusellenőrzés a kliens által küldött Content-Type-ot nézte,
 *   2. a kiterjesztés a kliens fájlnevéből jött, változtatás nélkül,
 *   3. a fájl a web által kiszolgált kepek/ könyvtárba került.
 *
 * Ezek a tesztek a feltöltés oldalát őrzik. A kiszolgálás oldalát (a
 * kepek/.htaccess fehérlistája) Apache-konfiguráció, azt itt nem tudjuk futtatni
 * — de a kettő EGYÜTT véd, egyik sem hagyható el.
 */
final class PhotoUploadSecurityTest extends TestCase {

    private const CHURCH_ID = 1;

    private array $createdFiles = [];
    private array $createdPhotoIds = [];

    protected function tearDown(): void {
        foreach ($this->createdPhotoIds as $id) {
            DB::table('photos')->where('id', $id)->delete();
        }
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) @unlink($file);
        }
        $this->createdPhotoIds = [];
        $this->createdFiles = [];
    }

    private function photoDir(): string {
        return dirname(__DIR__, 2) . '/kepek/templomok/' . self::CHURCH_ID;
    }

    /** Feltöltés-kísérlet; a felvett fájlt és DB-sort takarításra jegyezzük. */
    private function upload(string $tmpFile, string $clientName, string $clientType): \Eloquent\Photo {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = true;

        $photo = new \Eloquent\Photo();
        $photo->church_id = self::CHURCH_ID;
        $photo->flag = 'n';
        $photo->weight = 0;

        $photo->uploadFile([
            'name'     => $clientName,
            'type'     => $clientType,
            'tmp_name' => $tmpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmpFile),
        ]);

        if (isset($photo->id)) $this->createdPhotoIds[] = $photo->id;
        if (isset($photo->filename)) {
            $this->createdFiles[] = $this->photoDir() . '/' . $photo->filename;
            $this->createdFiles[] = $this->photoDir() . '/kicsi/' . $photo->filename;
        }

        return $photo;
    }

    private function tempJpeg(int $w = 60, int $h = 40): string {
        $file = tempnam(sys_get_temp_dir(), 'phototest');
        $image = imagecreatetruecolor($w, $h);
        imagejpeg($image, $file);
        imagedestroy($image);
        $this->createdFiles[] = $file;
        return $file;
    }

    private function tempGif(): string {
        $file = tempnam(sys_get_temp_dir(), 'phototest');
        $image = imagecreatetruecolor(20, 20);
        imagegif($image, $file);
        imagedestroy($image);
        $this->createdFiles[] = $file;
        return $file;
    }

    /* A bejelentett támadás: PHP-fájl, hamis Content-Type-pal. */
    public function testPlainPhpFileWithForgedContentTypeIsRejected(): void {
        $file = tempnam(sys_get_temp_dir(), 'phototest');
        file_put_contents($file, "<?php echo 'rce'; ?>");
        $this->createdFiles[] = $file;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('nem kép');

        $this->upload($file, 'shell.php', 'image/jpeg');
    }

    /*
     * A LÉNYEG: a kiterjesztés a fájl TARTALMÁBÓL jön, nem a kliens nevéből.
     * Valódi kép .php néven is csak .jpg-ként kerülhet ki.
     */
    public function testExtensionComesFromContentNotFromTheClientFilename(): void {
        $photo = $this->upload($this->tempJpeg(), 'shell.php', 'image/jpeg');

        self::assertStringEndsWith('.jpg', $photo->filename);
        self::assertStringNotContainsString('.php', $photo->filename);
    }

    /* Kettős kiterjesztés se csússzon át. */
    public function testDoubleExtensionIsNormalisedToTheRealType(): void {
        $photo = $this->upload($this->tempJpeg(), 'kep.jpg.php', 'image/jpeg');

        self::assertStringEndsWith('.jpg', $photo->filename);
        self::assertStringNotContainsString('php', $photo->filename);
    }

    /* A kliens Content-Type-ja önmagában semmit nem dönt el. */
    public function testForgedContentTypeCannotOverrideTheDetectedType(): void {
        $photo = $this->upload($this->tempGif(), 'akarmi.php', 'image/jpeg');

        self::assertStringEndsWith('.gif', $photo->filename, 'a GIF-et GIF-ként kell elmenteni');
    }

    /*
     * Polyglot: érvényes GIF-fejléc, mögötte PHP-kód. Ilyet a getimagesize() és a
     * GD is átenged — érvényes GIF, csak van mögötte szemét.
     *
     * Ezért NEM azt várjuk el, hogy elutasítsuk (nem is tudnánk megbízhatóan),
     * hanem azt, ami a támadást ténylegesen megállítja: a fájl SOHA nem kerülhet
     * ki futtatható kiterjesztéssel. GIF-ként kimentve az Apache képként szolgálja
     * ki, nem futtatja — a kepek/.htaccess fehérlistája pedig eleve csak
     * kép-kiterjesztést enged.
     */
    public function testPolyglotIsNeverStoredWithAnExecutableExtension(): void {
        $file = $this->tempGif();
        file_put_contents($file, "\n<?php echo 'polyglot'; ?>", FILE_APPEND);

        $photo = $this->upload($file, 'x.php', 'image/gif');

        self::assertStringEndsWith('.gif', $photo->filename);
        self::assertDoesNotMatchRegularExpression(
            '/\.(php|phtml|phar|php\d|inc)$/i',
            $photo->filename,
            'futtatható kiterjesztéssel semmi nem menthető el'
        );
    }

    /*
     * Sérült fájl (érvényes fejléc, olvashatatlan tartalom): a feldolgozás bukik,
     * és NEM maradhat utána fájl a lemezen.
     */
    public function testBrokenImageLeavesNoFileBehind(): void {
        $file = tempnam(sys_get_temp_dir(), 'phototest');
        // GIF-fejléc, mögötte csak szemét — a getimagesize() átengedi, a GD nem.
        file_put_contents($file, "GIF89a" . str_repeat("\x00", 40));
        $this->createdFiles[] = $file;

        $before = glob($this->photoDir() . '/*') ?: [];

        try {
            $this->upload($file, 'kep.gif', 'image/gif');
            self::fail('a sérült képet el kellett volna utasítani');
        } catch (\Exception $e) {
            self::assertStringContainsString('nem sikerült feldolgozni', $e->getMessage());
        }

        $after = glob($this->photoDir() . '/*') ?: [];
        self::assertSame(
            count($before), count($after),
            'a bukott feltöltés után nem maradhat fájl a lemezen'
        );
    }

    /* A rendes eset továbbra is működik — a javítás ne törje el a feltöltést. */
    public function testNormalJpegUploadStillWorks(): void {
        $photo = $this->upload($this->tempJpeg(120, 90), 'templom.JPG', 'image/jpeg');

        self::assertStringEndsWith('.jpg', $photo->filename);
        self::assertFileExists($this->photoDir() . '/' . $photo->filename);
        self::assertSame(120, (int) $photo->width);
        self::assertSame(90, (int) $photo->height);
    }

    /* A kicsinyített változatnak is el kell készülnie. */
    public function testThumbnailIsCreated(): void {
        $photo = $this->upload($this->tempJpeg(300, 200), 'templom.jpg', 'image/jpeg');

        self::assertFileExists($this->photoDir() . '/kicsi/' . $photo->filename);
    }
}
