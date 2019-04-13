# enginedetector
Detect which CMS a site is using

```php
try {
    $detector = new EngineDetector\Detector([
        'caching' => [
            'driver' => 'sqlite',
            'lifetime' => 30
        ]
    ]);

    // use internal Wappalyzer signatures database
    $detector->addHandler(new EngineDetector\Handler\Wappalyzer());
    // use external web service https://whatcms.org/API
    $detector->addHandler(new EngineDetector\Handler\WhatCms('__whatcms_api_key__'));

    $result = $detector->detect('http://wordpress.org');
    
    echo sprintf('%s %s', $result->getName(), $result->getVersion());
    
} catch (\EngineDetector\Exception\InvalidUrlException $e) {
    echo 'Invalid URL';
} catch (\EngineDetector\Exception\RequestException $e) {
    echo "Request error (website does not exist, or HTTP status code is different than 200)";
} catch (\EngineDetector\Exception\UnknownEngineException $e) {
    echo "CMS not detected";
} catch (Exception $e) {
    echo $e->getMessage();
}
```
