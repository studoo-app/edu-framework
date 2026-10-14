<?php

namespace Security;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Security\CsrfService;

class CsrfServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // Réinitialisation de la liste des jetons CSRF entre les tests
        $_SESSION = [];
    }

    public function testGetTokenGeneratesNotEmptyToken()
    {
        $token = CsrfService::getToken('route_test');
        $this->assertNotEquals('', $token);
        // bin2hex(random_bytes(32)) => 64 caractères hexadécimaux
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testGetTokenIsStableWithoutValidation()
    {
        $token1 = CsrfService::getToken('route_test');
        $token2 = CsrfService::getToken('route_test');
        $this->assertEquals($token1, $token2);
    }

    public function testTokensAreDistinctPerRoute()
    {
        $tokenA = CsrfService::getToken('route_a');
        $tokenB = CsrfService::getToken('route_b');
        $this->assertNotEquals($tokenA, $tokenB);
    }

    public function testValidateWithValidToken()
    {
        $token = CsrfService::getToken('route_test');
        $this->assertTrue(CsrfService::validate('route_test', $token));
    }

    public function testValidateWithInvalidToken()
    {
        CsrfService::getToken('route_test');
        $this->assertFalse(CsrfService::validate('route_test', 'token-invalide'));
    }

    public function testValidateWithNullToken()
    {
        CsrfService::getToken('route_test');
        $this->assertFalse(CsrfService::validate('route_test', null));
    }

    public function testValidateWithEmptyToken()
    {
        CsrfService::getToken('route_test');
        $this->assertFalse(CsrfService::validate('route_test', ''));
    }

    public function testValidateWithUnknownRoute()
    {
        $this->assertFalse(CsrfService::validate('route_inconnue', 'token'));
    }

    public function testTokenIsolationBetweenRoutes()
    {
        $tokenA = CsrfService::getToken('route_a');
        // Cloisonnement : le jeton de la route A ne valide pas la route B
        $this->assertFalse(CsrfService::validate('route_b', $tokenA));
    }

    public function testTokenRotationAfterSuccessfulValidation()
    {
        $tokenAvant = CsrfService::getToken('route_test');

        $this->assertTrue(CsrfService::validate('route_test', $tokenAvant));

        $tokenApres = CsrfService::getToken('route_test');
        // Rotation : le jeton a changé après la validation réussie
        $this->assertNotEquals($tokenAvant, $tokenApres);
        // L'ancien jeton n'est plus accepté
        $this->assertFalse(CsrfService::validate('route_test', $tokenAvant));
    }

    public function testRotationDoesNotAffectOtherRoutes()
    {
        $tokenA = CsrfService::getToken('route_a');
        $tokenB = CsrfService::getToken('route_b');

        $this->assertTrue(CsrfService::validate('route_a', $tokenA));

        // Le jeton de la route B reste valide après la rotation de la route A
        $this->assertTrue(CsrfService::validate('route_b', $tokenB));
    }

    public function testFailedValidationDoesNotRotateToken()
    {
        $tokenAvant = CsrfService::getToken('route_test');

        $this->assertFalse(CsrfService::validate('route_test', 'token-invalide'));

        // Un échec de validation ne renouvelle pas le jeton
        $this->assertEquals($tokenAvant, CsrfService::getToken('route_test'));
    }

    public function testGetTokenFieldContainsHiddenInput()
    {
        $token = CsrfService::getToken('route_test');
        $field = CsrfService::getTokenField('route_test');

        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="_csrf_token"', $field);
        $this->assertStringContainsString('value="' . $token . '"', $field);
    }

    public function testRequestIsCsrfTokenValidWithPostVar()
    {
        $token = CsrfService::getToken('route_test');

        $request = new Request('/test', 'POST');
        $request->setVars(['_csrf_token' => $token]);

        $this->assertTrue($request->isCsrfTokenValid('route_test'));
        // Le jeton a été renouvelé par la validation : une 2e validation échoue
        $this->assertFalse($request->isCsrfTokenValid('route_test'));
    }

    public function testRequestIsCsrfTokenValidWithInvalidPostVar()
    {
        CsrfService::getToken('route_test');

        $request = new Request('/test', 'POST');
        $request->setVars(['_csrf_token' => 'token-invalide']);

        $this->assertFalse($request->isCsrfTokenValid('route_test'));
    }

    public function testRequestIsCsrfTokenValidWithHeader()
    {
        if (function_exists('getallheaders') === true) {
            // Le header natif du SAPI est utilisé, le fallback $_SERVER n'est pas testable
            $this->markTestSkipped('getallheaders() natif : le fallback $_SERVER n\'est pas utilisé');
        }

        $token = CsrfService::getToken('route_test');

        $request = new Request('/test', 'POST');
        // getHearder() lit les en-têtes depuis $_SERVER quand getallheaders() n'existe pas
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        $this->assertTrue($request->isCsrfTokenValid('route_test'));

        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }
}
