<?php

/**
 * Plugin validation tests for Directions.
 *
 * Run with: ./vendor/bin/pest
 */
beforeEach(function () {
    $this->pluginPath = dirname(__DIR__);
    $this->manifestPath = $this->pluginPath.'/nativephp.json';
    $this->swiftFile = $this->pluginPath.'/resources/ios/Sources/DirectionsFunctions.swift';
    $this->kotlinFile = $this->pluginPath.'/resources/android/src/DirectionsFunctions.kt';
});

describe('Plugin Manifest', function () {
    it('has a valid nativephp.json file', function () {
        expect(file_exists($this->manifestPath))->toBeTrue();

        json_decode(file_get_contents($this->manifestPath), true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    });

    it('has required fields', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest)->toHaveKeys(['name', 'namespace', 'bridge_functions']);
        expect($manifest['name'])->toBe('keepcloud/plugin-directions');
        expect($manifest['namespace'])->toBe('Directions');
    });

    it('declares the Distances bridge function for both platforms', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $fn = $manifest['bridge_functions'][0];
        expect($fn['name'])->toBe('Directions.Distances');
        expect($fn['ios'])->toBe('DirectionsFunctions.Distances');
        expect($fn['android'])->toBe('com.keepcloud.plugins.directions.DirectionsFunctions.Distances');
    });

    it('declares its event under the plugin namespace', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['events'])->toBe(['Keepcloud\Directions\Events\DistancesReceived']);
    });
});

describe('Native Code', function () {
    it('has the iOS Swift file in resources/ios/Sources', function () {
        expect(file_exists($this->swiftFile))->toBeTrue();

        $content = file_get_contents($this->swiftFile);
        expect($content)->toContain('enum DirectionsFunctions');
        expect($content)->toContain('class Distances');
        expect($content)->toContain('BridgeFunction');
    });

    it('has the Android Kotlin file with the vendor package', function () {
        expect(file_exists($this->kotlinFile))->toBeTrue();

        $content = file_get_contents($this->kotlinFile);
        expect($content)->toContain('package com.keepcloud.plugins.directions');
        expect($content)->toContain('object DirectionsFunctions');
        expect($content)->toContain('BridgeFunction');
    });
});

describe('PHP Classes', function () {
    it('has the service provider under Keepcloud\\Directions', function () {
        $content = file_get_contents($this->pluginPath.'/src/DirectionsServiceProvider.php');
        expect($content)->toContain('namespace Keepcloud\Directions');
        expect($content)->toContain('class DirectionsServiceProvider');
    });

    it('has the facade', function () {
        $content = file_get_contents($this->pluginPath.'/src/Facades/Directions.php');
        expect($content)->toContain('namespace Keepcloud\Directions\Facades');
        expect($content)->toContain('class Directions extends Facade');
    });

    it('has the main implementation class', function () {
        $content = file_get_contents($this->pluginPath.'/src/Directions.php');
        expect($content)->toContain('namespace Keepcloud\Directions');
        expect($content)->toContain('function distances');
    });
});

describe('JavaScript Library', function () {
    it('ships a JS library with TypeScript definitions', function () {
        expect(file_exists($this->pluginPath.'/resources/js/index.js'))->toBeTrue();
        expect(file_exists($this->pluginPath.'/resources/js/index.d.ts'))->toBeTrue();

        $js = file_get_contents($this->pluginPath.'/resources/js/index.js');
        expect($js)->toContain('Directions.Distances');
        expect($js)->toContain('export');
    });
});

describe('Composer Configuration', function () {
    it('has a valid composer.json with the nativephp-plugin type', function () {
        $composer = json_decode(file_get_contents($this->pluginPath.'/composer.json'), true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($composer['name'])->toBe('keepcloud/plugin-directions');
        expect($composer['type'])->toBe('nativephp-plugin');
        expect($composer['extra']['nativephp']['manifest'])->toBe('nativephp.json');
        expect($composer['extra']['laravel']['providers'])
            ->toBe(['Keepcloud\Directions\DirectionsServiceProvider']);
    });
});
