<?php
include_once './vendor/autoload.php';
include_once 'wikidata.class.php';
include_once 'credentials.php';

use Wikibase\JsonDumpReader\JsonDumpFactory;
use DataValues\StringValue;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Entity\PropertyId;

$factory = new JsonDumpFactory();
$dumpReader = $factory->newExtractedDumpReader( '/mnt/data/wikidata-20160926-uncompressed.json' );
$dumpIterator = $factory->newStringDumpIterator( $dumpReader );
$api = new \Mediawiki\Api\MediawikiApi( "https://www.wikidata.org/w/api.php" );
$api->login( new \Mediawiki\Api\ApiUser( $wikiUser, $wikiPassword ) );
$dataValueClasses = array(
    'unknown' => 'DataValues\UnknownValue',
    'string' => 'DataValues\StringValue',
);
$wbFactory = new \Wikibase\Api\WikibaseFactory(
    $api,
    new DataValues\Deserializers\DataValueDeserializer( $dataValueClasses ),
    new DataValues\Serializers\DataValueSerializer()
);
$db = new PDO('mysql:host=127.0.0.1;dbname=fondations', 'root', 'root12345', array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
));


# Download dump if doesn't already exists
if (!file_exists("/mnt/data/wikidata-20160926-uncompressed.json")) {
	echo "Downloading wikidata dump. \n";	
	die();	
	file_put_contents("dump.json.bz2", fopen("https://dumps.wikimedia.org/wikidatawiki/entities/latest-all.json.bz2", 'r'));
}


foreach ( $dumpIterator as $jsonLine ) {
	$line = new Wikidata($jsonLine);
	if ($line->isInstanceOf(Array ("708676", "18325436", "163740")) and $line->name() != "") {
		$req = $db->prepare("SELECT name, gov_id FROM foundations WHERE foundations.name LIKE :name LIMIT 30;");
		$tmp = $line->name() . " %";
		$req->bindParam(":name", $tmp);
		$req->execute();
		echo "trying " . $line->name() . "\n";
		$res = $req->fetchAll();
		echo "found " . sizeof($res) . " results \n";
		foreach ($res as $ans) {
			echo $line->name() . " = " . $ans['name'] . "\n";
		}
		if (sizeof($res) > 1) {
			echo "Too many matches, continuing \n";
			echo "\n";
			echo "\n";
			continue;
		} else {
			if (    strtolower($line->name()) . " inc." == strtolower($ans['name'])
			     || strtolower($line->name()) . " ltd." == strtolower($ans['name'])
			     || strtolower($line->name()) . " usa" == strtolower($ans['name'])
			     || strtolower($line->name()) . " incorporated" == strtolower($ans['name'])
			     || strtolower($line->name()) . " foundation" == strtolower($ans['name'])
			     || strtolower($line->name()) . " found inc." == strtolower($ans['name'])
			     || strtolower($line->name()) . " fund inc." == strtolower($ans['name'])
			     || strtolower($line->name()) . " usa inc." == strtolower($ans['name'])
			     || strtolower($line->name()) . " corp" == strtolower($ans['name'])
			     || strtolower($line->name()) . " fund" == strtolower($ans['name'])
			     || strtolower($line->name()) . " company" == strtolower($ans['name'])
			     || strtolower($line->name()) . " association" == strtolower($ans['name'])
			     || strtolower($line->name()) . " institute" == strtolower($ans['name'])
			     || strtolower($line->name()) . " international" == strtolower($ans['name'])
			     || strtolower($line->name()) . " international inc." == strtolower($ans['name'])
			     || strtolower($line->name()) . " education fund" == strtolower($ans['name'])
			     || strtolower($line->name()) . " foundation inc." == strtolower($ans['name'])) {
					echo "Perfect match \n";

			    #importing it into wikidata
			if ($line->id() 
			    && $wbFactory->newRevisionGetter()->getFromId($line->id())->getContent()->getData()->getStatements()->getByPropertyId( PropertyId::newFromNumber( 1297 ) )->isEmpty()) {

	    		    $gov_id = substr($ans['gov_id'], 3, 2) . "-" . substr($ans['gov_id'], -7);
	    		    echo "Adding '" . $gov_id . "' to the entity '". $line->name() ."' \n";
	    		    $wbFactory->newStatementCreator()->create(
	    			new PropertyValueSnak(
	    			    PropertyId::newFromNumber( 1297 ),
	    			    new StringValue( $gov_id )
	    			),
	    			$line->id()
	    		    );
	    		}
			} else if (sizeof($res) == 1)  {
					echo "Bad match \n ";
			}
		}
		echo "\n";
		echo "\n";
	}
}


