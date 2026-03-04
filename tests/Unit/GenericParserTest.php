<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Hcl\Hcl;

describe('GenericParser', function (): void {
    describe('parse', function (): void {
        test('parses official HCL spec example', function (): void {
            $hcl = <<<'HCL'
                io_mode = "async"

                service "http" "web_proxy" {
                  listen_addr = "127.0.0.1:8080"

                  process "main" {
                    command = ["/usr/local/bin/awesome-app", "server"]
                  }

                  process "mgmt" {
                    command = ["/usr/local/bin/awesome-app", "mgmt"]
                  }
                }
                HCL;

            $result = Hcl::parse($hcl);

            expect($result)->toHaveKey('io_mode');
            expect($result['io_mode'])->toBe('async');
            expect($result)->toHaveKey('service');
            expect($result['service'])->toHaveKey('http');
            expect($result['service']['http'])->toHaveKey('web_proxy');
            expect($result['service']['http']['web_proxy']['listen_addr'])->toBe('127.0.0.1:8080');
            expect($result['service']['http']['web_proxy']['process']['main']['command'])->toBe(['/usr/local/bin/awesome-app', 'server']);
            expect($result['service']['http']['web_proxy']['process']['mgmt']['command'])->toBe(['/usr/local/bin/awesome-app', 'mgmt']);
        });

        test('parses top-level attributes', function (): void {
            $hcl = <<<'HCL'
                name = "my-app"
                version = 1.0
                enabled = true
                tags = ["web", "api"]
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['name'])->toBe('my-app');
            expect($result['version'])->toBe(1.0);
            expect($result['enabled'])->toBe(true);
            expect($result['tags'])->toBe(['web', 'api']);
        });

        test('parses nested blocks with labels', function (): void {
            $hcl = <<<'HCL'
                resource "aws_instance" "web" {
                  ami           = "ami-12345"
                  instance_type = "t2.micro"
                }
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['resource']['aws_instance']['web']['ami'])->toBe('ami-12345');
            expect($result['resource']['aws_instance']['web']['instance_type'])->toBe('t2.micro');
        });

        test('parses blocks without labels', function (): void {
            $hcl = <<<'HCL'
                terraform {
                  required_version = ">= 1.0"
                }
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['terraform']['required_version'])->toBe('>= 1.0');
        });

        test('parses multiple blocks of same type', function (): void {
            $hcl = <<<'HCL'
                variable "region" {
                  default = "us-east-1"
                }

                variable "environment" {
                  default = "production"
                }
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['variable']['region']['default'])->toBe('us-east-1');
            expect($result['variable']['environment']['default'])->toBe('production');
        });

        test('parses deeply nested blocks', function (): void {
            $hcl = <<<'HCL'
                provider "aws" "primary" {
                  region = "us-east-1"

                  assume_role "admin" {
                    role_arn = "arn:aws:iam::123456789012:role/admin"
                  }
                }
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['provider']['aws']['primary']['region'])->toBe('us-east-1');
            expect($result['provider']['aws']['primary']['assume_role']['admin']['role_arn'])
                ->toBe('arn:aws:iam::123456789012:role/admin');
        });

        test('parses function calls as special structure', function (): void {
            $hcl = <<<'HCL'
                data = file("config.json")
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['data'])->toBe([
                '__function__' => 'file',
                '__args__' => ['config.json'],
            ]);
        });

        test('parses object values', function (): void {
            $hcl = <<<'HCL'
                settings = {
                  timeout = 30
                  retry   = true
                }
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['settings'])->toBe([
                'timeout' => 30,
                'retry' => true,
            ]);
        });

        test('parses references as interpolation strings', function (): void {
            $hcl = <<<'HCL'
                output = var.input
                HCL;

            $result = Hcl::parse($hcl);

            expect($result['output'])->toBe('${var.input}');
        });
    });

    describe('toJson', function (): void {
        test('converts HCL to JSON', function (): void {
            $hcl = <<<'HCL'
                name = "test"
                port = 8080
                HCL;

            $json = Hcl::toJson($hcl);
            $data = json_decode($json, true);

            expect($data['name'])->toBe('test');
            expect($data['port'])->toBe(8_080);
        });

        test('converts official spec example to JSON', function (): void {
            $hcl = file_get_contents(testFixture('official-spec.hcl'));
            $json = Hcl::toJson($hcl);
            $data = json_decode($json, true);

            expect($data['io_mode'])->toBe('async');
            expect($data['service']['http']['web_proxy']['listen_addr'])->toBe('127.0.0.1:8080');
        });
    });

    describe('fromJson', function (): void {
        test('converts JSON to HCL', function (): void {
            $json = '{"name": "test", "port": 8080}';

            $hcl = Hcl::fromJson($json);

            expect($hcl)->toContain('name = "test"');
            expect($hcl)->toContain('port = 8080');
        });

        test('converts complex JSON to HCL blocks', function (): void {
            $json = json_encode([
                'io_mode' => 'async',
                'service' => [
                    'http' => [
                        'web_proxy' => [
                            'listen_addr' => '127.0.0.1:8080',
                        ],
                    ],
                ],
            ]);

            $hcl = Hcl::fromJson($json);

            expect($hcl)->toContain('io_mode = "async"');
            expect($hcl)->toContain('service "http" "web_proxy"');
            expect($hcl)->toContain('listen_addr = "127.0.0.1:8080"');
        });
    });

    describe('roundtrip', function (): void {
        test('HCL -> JSON -> HCL preserves data', function (): void {
            $original = <<<'HCL'
                name = "roundtrip-test"
                version = 42
                enabled = true
                tags = ["a", "b", "c"]
                HCL;

            $json = Hcl::toJson($original);
            $backToHcl = Hcl::fromJson($json);
            $reparsed = Hcl::parse($backToHcl);

            expect($reparsed['name'])->toBe('roundtrip-test');
            expect($reparsed['version'])->toBe(42);
            expect($reparsed['enabled'])->toBe(true);
            expect($reparsed['tags'])->toBe(['a', 'b', 'c']);
        });
    });
});
