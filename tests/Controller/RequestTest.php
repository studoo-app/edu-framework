<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testSetHander()
    {
        $request = new Request("/test", "GET");
        $request->setHander("test");
        $this->assertEquals("test", $request->getHander());
    }

    public function testGetHttpMethod()
    {
        $request = new Request("/test", "GET");
        $this->assertEquals("GET", $request->getHttpMethod());
    }

    public function testSetHttpMethod()
    {
        $request = new Request("/test", "GET");
        $request->setHttpMethod("POST");
        $this->assertEquals("POST", $request->getHttpMethod());
    }

    public function testGetVars()
    {
        $request = new Request("/test", "GET");
        $this->assertEquals([], $request->getVars());
    }

    public function testGetRoute()
    {
        $request = new Request("/test", "GET");
        $this->assertEquals("/test", $request->getRoute());
    }

    public function testSetRoute()
    {
        $request = new Request("/test", "GET");
        $request->setRoute("/test2");
        $this->assertEquals("/test2", $request->getRoute());
    }

    public function testSetVars()
    {
        $request = new Request("/test", "GET");
        $request->setVars(["test" => "test"]);
        $this->assertEquals(["test" => "test"], $request->getVars());
    }

    public function testGetValide()
    {
        $request = new Request("/test", "GET");
        $request->setVars(["testVars" => "test"]);
        $this->assertEquals("test", $request->get("testVars"));
    }

    public function testGetHander()
    {
        $request = new Request("/test", "GET");
        $request->setHander("Controller\HomeController");
        $this->assertEquals("Controller\HomeController", $request->getHander());
    }

    public function testGetActionDefault()
    {
        $request = new Request("/test", "GET");
        $this->assertEquals("execute", $request->getAction());
    }

    public function testSetAction()
    {
        $request = new Request("/test", "GET");
        $request->setAction("index");
        $this->assertEquals("index", $request->getAction());
    }

    public function testSetActionAndHander()
    {
        $request = new Request("/test", "GET");
        $request->setHander("Controller\MedecinController")->setAction("index");
        $this->assertEquals("Controller\MedecinController", $request->getHander());
        $this->assertEquals("index", $request->getAction());
    }

    public function testSetFilesSimpleField()
    {
        $request = new Request("/test", "POST");
        $request->setFiles([
            'fichier' => [
                'name' => 'medecins.csv',
                'type' => 'text/csv',
                'size' => 123,
                'tmp_name' => '/tmp/phpXYZ',
                'error' => UPLOAD_ERR_OK,
            ],
        ]);

        // La structure est normalisée : une liste de fichiers même pour un champ simple
        $this->assertEquals(
            [[
                'name' => 'medecins.csv',
                'type' => 'text/csv',
                'size' => 123,
                'tmp_name' => '/tmp/phpXYZ',
                'error' => UPLOAD_ERR_OK,
            ]],
            $request->getFile('fichier')
        );
        $this->assertNull($request->getFile('inconnu'));
        $this->assertTrue($request->hasFile('fichier'));
        $this->assertFalse($request->hasFile('inconnu'));
        $this->assertEquals(1, count($request->getFiles()));
    }

    public function testSetFilesMultipleField()
    {
        $request = new Request("/test", "POST");
        $request->setFiles([
            'galerie' => [
                'name' => ['a.png', 'b.png'],
                'type' => ['image/png', 'image/png'],
                'size' => [123, 456],
                'tmp_name' => ['/tmp/phpA', '/tmp/phpB'],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            ],
        ]);

        // La structure native de $_FILES en multi-upload est normalisée en liste de fichiers
        $this->assertEquals(
            [
                ['name' => 'a.png', 'type' => 'image/png', 'size' => 123, 'tmp_name' => '/tmp/phpA', 'error' => UPLOAD_ERR_OK],
                ['name' => 'b.png', 'type' => 'image/png', 'size' => 456, 'tmp_name' => '/tmp/phpB', 'error' => UPLOAD_ERR_OK],
            ],
            $request->getFile('galerie')
        );
    }

    public function testIsValid()
    {
        $request = new Request("/test", "POST");
        $request->setFiles([
            'fichier' => [
                'name' => 'medecins.csv',
                'type' => 'text/csv',
                'size' => 123,
                'tmp_name' => '/tmp/phpXYZ',
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $this->assertTrue($request->isValid('fichier'));
        $this->assertTrue($request->isValid('fichier', 0));
        $this->assertFalse($request->isValid('inconnu'));
        $this->assertFalse($request->isValid('fichier', 10));
    }

    public function testIsValidWithError()
    {
        $request = new Request("/test", "POST");
        $request->setFiles([
            'fichier' => [
                'name' => '',
                'type' => '',
                'size' => 0,
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ],
        ]);
        // Champ laissé vide : hasFile() vrai mais isValid() faux
        $this->assertTrue($request->hasFile('fichier'));
        $this->assertFalse($request->isValid('fichier'));
    }

    public function testGetExtension()
    {
        $request = new Request("/test", "POST");
        $request->setFiles([
            'fichier' => [
                'name' => 'medecins.CSV',
                'type' => 'text/csv',
                'size' => 123,
                'tmp_name' => '/tmp/phpXYZ',
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $this->assertEquals('csv', $request->getExtension('fichier'));
        $this->assertNull($request->getExtension('inconnu'));
    }

    public function testMoveWithFieldNotExist()
    {
        $request = new Request("/test", "POST");
        $this->assertFalse($request->move('inconnu', '/tmp/destination.csv'));
    }

    public function testMoveWithError()
    {
        $request = new Request("/test", "POST");
        $request->setFiles([
            'fichier' => [
                'name' => 'medecins.csv',
                'type' => 'text/csv',
                'size' => 123,
                'tmp_name' => '/tmp/phpXYZ',
                'error' => UPLOAD_ERR_INI_SIZE,
            ],
        ]);
        $this->assertFalse($request->move('fichier', '/tmp/destination.csv'));
    }

    public function testMoveWithNotUploadedFile()
    {
        $request = new Request("/test", "POST");
        $request->setFiles([
            'fichier' => [
                'name' => 'medecins.csv',
                'type' => 'text/csv',
                'size' => 123,
                'tmp_name' => '/tmp/fichier-factice.csv',
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        // Sans vrai téléversement, is_uploaded_file() échoue et move() retourne false
        $this->assertFalse($request->move('fichier', '/tmp/destination.csv'));
    }
}
