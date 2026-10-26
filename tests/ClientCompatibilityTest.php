<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ClientCompatibilityTest extends TestCase
{
  private array $requests = [];
  private MockHandler $mock;

  public function testCreatesTheDefaultGuzzle7Client(): void
  {
    $client = new Google_Client();

    self::assertSame(Google_Client::API_BASE_PATH, (string) $client->getHttpClient()->getConfig('base_uri'));
    self::assertFalse($client->getHttpClient()->getConfig('http_errors'));
    self::assertInstanceOf(
        Google_AuthHandler_Guzzle6AuthHandler::class,
        Google_AuthHandler_AuthHandlerFactory::build($client->getCache())
    );
  }

  public function testExchangesAnAuthorizationCode(): void
  {
    $client = $this->client([new Response(200, [], '{"access_token":"access-token","expires_in":3600}')]);
    $token = $client->fetchAccessTokenWithAuthCode('authorization-code');
    parse_str((string) $this->requests[0]['request']->getBody(), $body);

    self::assertSame('access-token', $token['access_token']);
    self::assertSame($token, $client->getAccessToken());
    self::assertSame('authorization-code', $body['code']);
    self::assertSame('authorization_code', $body['grant_type']);
  }

  public function testRefreshesAnAccessToken(): void
  {
    $client = $this->client([new Response(200, [], '{"access_token":"new-token","expires_in":3600}')]);
    $token = $client->fetchAccessTokenWithRefreshToken('refresh-token');
    parse_str((string) $this->requests[0]['request']->getBody(), $body);

    self::assertSame('new-token', $token['access_token']);
    self::assertSame('refresh-token', $token['refresh_token']);
    self::assertSame('refresh_token', $body['grant_type']);
  }

  public function testAddsTheBearerTokenToApiRequests(): void
  {
    $client = $this->client([new Response(200)]);
    $client->setAccessToken('access-token');
    $client->setScopes(['profile']);
    $client->authorize()->get('https://example.test/resource');

    self::assertSame('Bearer access-token', $this->mock->getLastRequest()->getHeaderLine('Authorization'));
  }

  public function testAddsTheApiKeyToRequests(): void
  {
    $client = $this->client([new Response(200)]);
    $client->setDeveloperKey('api-key');
    $client->authorize()->get('https://example.test/resource');
    parse_str($this->mock->getLastRequest()->getUri()->getQuery(), $query);

    self::assertSame('api-key', $query['key']);
  }

  public function testRevokesAnAccessToken(): void
  {
    $client = $this->client([new Response(200)]);

    self::assertTrue($client->revokeToken('access-token'));
    self::assertSame('POST', $this->requests[0]['request']->getMethod());
    self::assertSame('token=access-token', (string) $this->requests[0]['request']->getBody());
  }

  public function testPreservesApiErrorResponses(): void
  {
    $client = $this->client([new Response(401, [], '{"error":{"message":"unauthorized"}}')]);

    $this->expectException(Google_Service_Exception::class);
    $this->expectExceptionCode(401);
    $client->execute(new Request('GET', 'https://example.test/resource'));
  }

  public function testCreatesMediaUploadStreams(): void
  {
    $upload = new Google_Http_MediaFileUpload(
        new Google_Client(),
        new Request('POST', 'https://www.googleapis.com/drive/v3/files'),
        'text/plain',
        'file contents'
    );

    self::assertSame('file contents', (string) $upload->getRequest()->getBody());
    self::assertSame('uploadType=media', $upload->getRequest()->getUri()->getQuery());
  }

  public function testLoadsGeneratedGoogleServiceModels(): void
  {
    $service = new Google_Service_Drive(new Google_Client());
    $file = new Google_Service_Drive_DriveFile(['name' => 'test.txt']);
    $files = new Google_Service_Drive_FileList(['files' => [$file]]);

    self::assertInstanceOf(Google_Service_Resource::class, $service->files);
    self::assertSame('test.txt', $file->getName());
    self::assertSame([$file], iterator_to_array($files));
  }

  public function testPreservesModelArrayAccess(): void
  {
    $file = new Google_Service_Drive_DriveFile();
    $file['name'] = 'test.txt';
    $file['customField'] = 'value';

    self::assertSame('test.txt', $file['name']);
    self::assertTrue(isset($file['customField']));
    unset($file['customField']);
    self::assertFalse(isset($file['customField']));
    self::assertCount(0, new Google_Service_Drive_FileList());
  }

  public function testCreatesResumableUploadStreams(): void
  {
    $client = $this->client([
      new Response(200, ['Location' => 'https://example.test/upload/session']),
      new Response(200, [], '{"id":"uploaded-file"}'),
    ]);
    $upload = new Google_Http_MediaFileUpload(
        $client,
        new Request('POST', 'https://www.googleapis.com/drive/v3/files'),
        'text/plain',
        'file contents',
        true
    );
    $upload->setFileSize(strlen('file contents'));
    $response = $upload->nextChunk('file contents');

    self::assertInstanceOf(ResponseInterface::class, $response);
    self::assertSame('PUT', $this->requests[1]['request']->getMethod());
    self::assertSame('file contents', (string) $this->requests[1]['request']->getBody());
  }

  public function testParsesBatchResponseStreams(): void
  {
    $batch = new Google_Http_Batch(new Google_Client());
    $batch->add(new Request('GET', 'https://example.test/resource'), 'one');
    $body = "--batch_response\r\nContent-Type: application/http\r\nContent-ID: response-one\r\n\r\n"
        . "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"ok\":true}\r\n--batch_response--\r\n";
    $responses = $batch->parseResponse(
        new Response(200, ['Content-Type' => 'multipart/mixed; boundary=batch_response'], $body)
    );

    self::assertInstanceOf(ResponseInterface::class, $responses['response-one']);
    self::assertSame('{"ok":true}', trim((string) $responses['response-one']->getBody()));
  }

  public function testBootstrapsTheCmsAndItsCacheOnPhp83(): void
  {
    $script = 'require ' . var_export(dirname(__DIR__) . '/public_html/system/classes/Core.php', true) . ';'
        . '$cache = \Phpfastcache\CacheManager::getInstance("files");'
        . '$item = $cache->getItem("jwt-runtime"); $item->set("ok");'
        . 'if (!$cache->save($item) || $cache->getItem("jwt-runtime")->get() !== "ok") {'
        . 'throw new RuntimeException("CMS cache round trip failed"); }'
        . 'new \PHPMailer(); echo join_path("/var/", "/www/", "html");';
    exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($script) . ' 2>&1', $output, $status);

    self::assertSame(0, $status, implode("\n", $output));
    self::assertSame('/var/www/html', trim(implode("\n", $output)));
  }

  public function testResolvesCmsPackageDependenciesOnPhp83(): void
  {
    $script = 'require ' . var_export(dirname(__DIR__) . '/public_html/system/classes/Core.php', true) . ';'
        . '$method = new ReflectionMethod(\\system\\classes\\Core::class, "_solve_dependencies_graph");'
        . '$graph = ["dashboard" => ["core"], "core" => [], "mission_control" => ["dashboard", "core"]];'
        . '$ordered = $method->invoke(null, $graph);'
        . '$cycle = $method->invoke(null, ["dashboard" => ["core"], "core" => ["dashboard"]]);'
        . 'echo json_encode([$ordered, $cycle], JSON_THROW_ON_ERROR);';
    exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($script) . ' 2>&1', $output, $status);

    self::assertSame(0, $status, implode("\n", $output));
    self::assertSame([
      ['success' => true, 'data' => ['core', 'dashboard', 'mission_control']],
      ['success' => false, 'data' => 'Circular dependency: core -> dashboard'],
    ], json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR));
  }

  public function testSkipsMissingCmsModuleFamiliesOnPhp83(): void
  {
    $script = 'require ' . var_export(dirname(__DIR__) . '/public_html/system/classes/Core.php', true) . ';'
        . 'set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });'
        . '$property = new ReflectionProperty(\\system\\classes\\Core::class, "packages");'
        . '$property->setValue(null, ['
        . '"core" => ["id" => "core", "enabled" => true, "modules" => ["background/global" => ["global.php"]]],'
        . '"dashboard" => ["id" => "dashboard", "enabled" => true, "modules" => []],'
        . '"disabled" => ["id" => "disabled", "enabled" => false, "modules" => ["background/global" => ["disabled.php"]]]]);'
        . 'echo json_encode(['
        . '\\system\\classes\\Core::getPackagesModules("background/global"),'
        . '\\system\\classes\\Core::getPackagesModules("background/global", "dashboard"),'
        . '\\system\\classes\\Core::getPackagesModules("background/local", "missing"),'
        . '\\system\\classes\\Core::getPackagesModules("background/global", null, true),'
        . '\\system\\classes\\Core::getPackagesModules("background/global", "disabled"),'
        . '\\system\\classes\\Core::getPackagesModules("background/global", "core"),'
        . '\\system\\classes\\Core::getPackagesModules("missing"),'
        . '\\system\\classes\\Core::getPackagesModules(null, "missing")], JSON_THROW_ON_ERROR);';
    exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($script) . ' 2>&1', $output, $status);

    self::assertSame(0, $status, implode("\n", $output));
    self::assertSame([
      ['core' => ['global.php']],
      [],
      [],
      ['core' => ['global.php'], 'disabled' => ['disabled.php']],
      [],
      ['global.php'],
      [],
      [],
    ], json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR));
  }

  public function testReadsMissionControlRendererMetadataOnPhp83(): void
  {
    $script = 'require ' . var_export(dirname(__DIR__) . '/public_html/system/classes/BlockRenderer.php', true) . ';'
        . 'class PublicMissionControlTestRenderer extends \\system\\classes\\BlockRenderer {'
        . 'protected static $DEFAULT_SIZE = ["rows" => 1, "cols" => 3];'
        . 'protected static $ARGUMENTS = ["topic" => ["default" => "/camera"]]; }'
        . 'class PrivateMissionControlTestRenderer extends PublicMissionControlTestRenderer {'
        . 'protected static $PRIVATE = true; }'
        . 'echo json_encode(['
        . 'PublicMissionControlTestRenderer::is_private(),'
        . 'PrivateMissionControlTestRenderer::is_private(),'
        . 'json_decode(PublicMissionControlTestRenderer::get_default_JSON(), true, 512, JSON_THROW_ON_ERROR)'
        . '], JSON_THROW_ON_ERROR);';
    exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($script) . ' 2>&1', $output, $status);

    self::assertSame(0, $status, implode("\n", $output));
    self::assertSame([
      false,
      true,
      [
        'shape' => ['rows' => 1, 'cols' => 3],
        'renderer' => 'PublicMissionControlTestRenderer',
        'title' => 'New PublicMissionControlTestRenderer block...',
        'subtitle' => 'New PublicMissionControlTestRenderer block subtitle.',
        'args' => ['topic' => '/camera'],
      ],
    ], json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR));
  }

  public function testRendersMissionControlFormsOnPhp83(): void
  {
    $classes = dirname(__DIR__) . '/public_html/system/classes/';
    $script = 'require ' . var_export($classes . 'BlockRenderer.php', true) . ';'
        . 'require ' . var_export($classes . 'MissionControl.php', true) . ';'
        . 'function generateFormByLayout(&$layout, $formID = null, $formClass = null, '
        . '$method = null, $action = null, &$values = []) {'
        . '$GLOBALS["forms"][] = ["id" => $formID, "values" => $values, "layout" => $layout]; }'
        . 'class EnumMissionControlTestConfiguration extends MissionControlConfiguration {'
        . 'protected static $OPTIONS = ["mode" => ["name" => "Mode", "type" => "enum", '
        . '"enum_values" => ["live", "paused"], "default" => "live"]]; }'
        . 'class EnumMissionControlTestRenderer extends \\system\\classes\\BlockRenderer {'
        . 'protected static $ARGUMENTS = ["view" => ["name" => "View", "type" => "enum", '
        . '"enum_values" => ["front", "bottom"], "default" => "front"]]; }'
        . '$defaults = new ReflectionMethod(MissionControlConfiguration::class, "get_default_options");'
        . '$options = $defaults->invoke(null); $options["resolution"] = 12;'
        . 'ob_start(); MissionControlConfiguration::render_modal("grid", "mission", $options);'
        . 'EnumMissionControlTestConfiguration::render_modal("enum-grid", "mission", ["mode" => "live"]);'
        . '$control = new MissionControl("grid", []);'
        . '$renderer = new EnumMissionControlTestRenderer($control);'
        . '$shape = ["rows" => 1, "cols" => 1];'
        . '$arguments = ["topic" => "/camera", "view" => "bottom"]; $blockOptions = ["show_header" => false];'
        . '$renderer->draw("block", "block-id", "Camera", "Live", $shape, $arguments, $blockOptions);'
        . 'ob_end_clean(); echo json_encode($GLOBALS["forms"], JSON_THROW_ON_ERROR);';
    exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=1 -r ' . escapeshellarg($script) . ' 2>&1', $output, $status);

    self::assertSame(0, $status, implode("\n", $output));
    $forms = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
    self::assertCount(3, $forms);
    self::assertSame('mission-grid-form', $forms[0]['id']);
    self::assertSame(12, $forms[0]['values']['resolution']);
    self::assertSame(['Live', 'Paused'], $forms[1]['layout']['mode']['placeholder']);
    self::assertSame('mission-control-block-block-id-form', $forms[2]['id']);
    self::assertSame(['Front', 'Bottom'], $forms[2]['layout']['view']['placeholder']);
    self::assertSame([
      'topic' => '/camera',
      'view' => 'bottom',
      'title' => 'Camera',
      'subtitle' => 'Live',
    ], $forms[2]['values']);
  }

  private function client(array $responses): Google_Client
  {
    $this->mock = new MockHandler($responses);
    $stack = HandlerStack::create($this->mock);
    $stack->push(Middleware::history($this->requests));
    $client = new Google_Client([
      'client_id' => 'test-client',
      'client_secret' => 'test-secret',
      'redirect_uri' => 'https://example.test/oauth/callback',
    ]);
    $client->setHttpClient(new Client(['handler' => $stack, 'http_errors' => false]));

    return $client;
  }
}
