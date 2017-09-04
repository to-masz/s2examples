# S2 exmples

This repository contains the complete example on how to use Google's S2 library in PHP. Please follow this README to understand the contents. 

It is a live example from my presentation **Having fun with geospatial data in your software. An introduction to Google's S2 geometry library.**

#### Preliminary notes

S2 library is written originally in C++. It was ported to several languages, unfortunately there is no really good port in PHP. 
**It gives you great opportunity to contribute to PHP world and rewrite the library as other people did for Java or Python**.

I recommend to use `NicklasWallgren/s2-geometry-library-php` fork, that is a fork of another fork of another fork of ... Simply everyone in that chain have added or fixed quite important part of the library. Nicklas seems to be one, that has time to review and merge all pull requests.
 
```bash
composer require NicklasWallgren/s2-geometry-library-php
```

For now there are few changes pending, so this repo uses my fork.


#### Simple examples

##### S2 cell identifiers

Check `utils/examples.php` for simple examples of using `S2CellId` class.

```php
$lat = 52.4049292;
$lng = 16.9096754;

$s2CellId = S2CellId::fromLatLng(S2LatLng::fromDegrees($lat, $lng));

echo $s2CellId->id() . PHP_EOL;
echo decbin($s2CellId->id()) . PHP_EOL;
echo $s2CellId->toToken() . PHP_EOL;
```

To create S2 cell identifier you can simply use pair of latitude and longitude coordinates. 
Then you can get its representation as 64-bit integer, binary string or string token. The above code output following results:

```
5117315353002051839
100011100000100010110110011001101101011010101110000100011111111
47045b336b5708ff
```

Please note the meaning of that binary representation is

bits |  | meaning 
--- | --- | ---
0-2 | 100 | The face of the cube the location belongs to (from 0 to 5). The example location belongs to the 4th face.
3-63 | 01110000010001011011001100110110101101010111000010001111111 | Each 2 bits define the given node at each level in quadtree.
64 | 1 | Whether the location is precise (1) or approximated (0).


As the hierarchy of the S2 cells is 30 levels quadtreee, you often want to get a less precise identifier.

```php
$s2CellIdLvl10 = $s2CellId->parent(10);

echo $s2CellIdLvl10->id() . PHP_EOL;
echo decbin($s2CellIdLvl10->id()) . PHP_EOL;
echo $s2CellIdLvl10->toToken() . PHP_EOL;
```

The above example gets the 10th level of the cell id. Please note the level is the level of the quadtree. It means the 1st level is the less precise and 30th is the exact location.
The result of the code is (please note the difference with previous output):

```
5117315132157853696
100011100000100010110110000000000000000000000000000000000000000
47045b
```

##### S2 cell neighbours

As the cell identifier belongs to quadtree, it has only 3 neighbours in the tree (other nodes from the same parent node).
But each cell has 8 neighbours, fortunately it is very easy to get them all.

```php
$neighbors = [];
$s2CellId->getAllNeighbors($s2CellId->level(), $neighbors);

foreach ($neighbors as $neighbor) {
    echo $neighbor->id() . PHP_EOL;
    echo decbin($neighbor->id()) . PHP_EOL;
    echo $neighbor->toToken() . PHP_EOL;
    echo PHP_EOL;
}
```

Please note 4th, 5th and 7th neighbour are from the same parent node and are very close on the Hilbert curve. 

```
5117315353002051671
100011100000100010110110011001101101011010101110000100001010111
47045b336b570857

5117315353002052011
100011100000100010110110011001101101011010101110000100110101011
47045b336b5709ab

5117315353002051669
100011100000100010110110011001101101011010101110000100001010101
47045b336b570855

5117315353002051837
100011100000100010110110011001101101011010101110000100011111101
47045b336b5708fd

5117315353002051833
100011100000100010110110011001101101011010101110000100011111001
47045b336b5708f9

5117315353002051841
100011100000100010110110011001101101011010101110000100100000001
47045b336b570901

5117315353002051835
100011100000100010110110011001101101011010101110000100011111011
47045b336b5708fb

5117315353002051843
100011100000100010110110011001101101011010101110000100100000011
47045b336b570903
```

#### Indexing S2 cells

Here I use the Elasticsearch as a storage for S2 cells. I recommend official docker image and PHP client `elasticsearch/elasticsearch`.

```bin
docker run --name elasticsearch-test -p 9200:9200 -e "http.host=0.0.0.0" -e "transport.host=127.0.0.1" docker.elastic.co/elasticsearch/elasticsearch:5.5.2
```

The code you can find in `utils/indexer.php`.

Our goal is to make the cell ID findable by any ID of its parents in the cell hierarchy (in quadtree).

Then we can use inverted index to store all IDs as a way for very effective querying it later.

You can define your index mapping for s2 cells field like following.

```
's2cells' => [
    'type' => 'text',
    'analyzer' => 'whitespace'
]
```

And we want to index all the cell IDs from the hierarchy. The method `isFace()` returns true for the root node of the quadtree.

```php
$s2 = S2CellId::fromLatLng(S2LatLng::fromDegrees($data[1], $data[2]));
$s2cell = $s2->toToken();
$s2cells = [$s2cell];
while (!$s2->isFace()) {
    $s2 = $s2->parent();
    $s2cells[] = $s2->toToken();
}
```

Check the indexer code for more details.


#### Running web example

After indexing a data run PHP webserver and check [localhost:8080](http://localhost:8080).
You should also provide your Key for accessing Google Maps Javascript API in `web/index.html`. 

```bash
php -S localhost:8080 -t web/
```

##### Region convering

Of the main goal of S2 library is to provide efficient way to approximate even very complicated polygons. 

Currently this project only supports covering the rectangle area. It is because there is a need for some fixes in library to fully support polygon covering.

Covering a region is as simple as using `S2RegionCoverer` class. It accepts any implementation of `S2Region` interface. 

```php
$region = new S2LatLngRect(S2LatLng::fromDegrees($ne[0], $sw[1]), S2LatLng::fromDegrees($sw[0], $ne[1]));
```
```php
$covering = [];
$regionCoverer = new S2RegionCoverer();
$regionCoverer->setMaxCells($requestBody['maxCells']);
$regionCoverer->setMinLevel($requestBody['minLevel']);
$regionCoverer->setMaxLevel($requestBody['maxLevel']);
$regionCoverer->getCovering($region, $covering);
```

As a result we have a list of `S2CellId` objects in `$covering`. They respect the parameters provided to region coverer: min cell level, max cell level, max number of cells.

See `src/RegoinCovererAction.php` for more details.

##### Querying 

And at the end one of the coolest search technology combinations out there are the ability to combine geo and search. 

Querying the Elasticsearch in our configuration for this combination is very easy.

```php
$must = [];
if (!empty($title)) {
    $must[] = [ 'match' => [ 'title' => $title ] ];
}
if (!empty($s2cells)) {
    $must[] = [ 'match' => [ 's2cells' => ['query' => $s2cells, 'operator' => 'OR'] ] ];
}
        
$params = [
    'index' => 'example',
    'type' => 'my_type',
    'body' => [
        'size' => 1000,
        'query' => [
            'bool' => [
                'must' => $must
            ]
        ]
    ]
];
```

See `src/SearchAction.php` for more details.