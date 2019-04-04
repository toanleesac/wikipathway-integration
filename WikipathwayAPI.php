<?php

class WikiPathwayModelTest {

    public function annotateWikiPathwayModel($svg_dir, $gpml_dir, $gene_symbols) {
        foreach (glob($svg_dir . "*.svg") as $filename) {
            $saved = false;

            $words = explode("_", basename($filename));
            $name = "";

            //Wxxx
            $pathway_code = $words[count($words) - 2];
            //$category_id = 3;

            foreach (glob($gpml_dir . "*.gpml") as $gpml_filename) {

                if (strpos($gpml_filename, $pathway_code) !== false) {
                    $gpml_xpath = $this->loadGpml($gpml_filename);
                    $wikipathway_category = $this->findWikipathwayCategory($gpml_xpath);

                    //save wikipathway category
                }
            }

            $name = $this->getPathwayName($words);

            if (file_exists($filename)) {

                $xml = simplexml_load_file($filename);

                $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

                $doc = new DOMDocument('1.0', 'utf-8');

                $doc->loadXML($xml->asXML());

                $xpath = new DOMXPath($doc);
                $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');


                //$listNodes = $xml->xpath('//svg:g/svg:text');
                $rootNode = $xpath->query('/svg:svg')->item(0);

                $this->setSvgViewBox($rootNode);

                foreach ($gene_symbols as $gene_symbol) {

                    $elements = $xpath->query('//*[svg:text="' . $gene_symbol['gene_symbol'] . '"]');
                    if (!is_null($elements) && !empty($elements)) {

                        foreach ($elements as $element) {

                            $nodes = $element->childNodes;
                            foreach ($nodes as $node) {

                                $style = $node->getAttribute("style");

                                if (strpos($style, "fill:none") !== false) {
                                    $style = str_replace("fill:none;", "fill:#00FF00; fill-opacity:0.6;", $style);
                                    $node->removeAttribute("style");
                                    $node->setAttribute("style", $style);

                                    //save wikipathway info to database

                                }

                            }

                            $linkNode = $doc->createElement("a");

                            $linkNode->setAttribute("xlink:href", "javascript:void(0)");
                            $linkNode->setAttribute("onclick", "wikipathway_assays(this, '" . $gene_symbol['gene_symbol'] . "');");
                            while ($nodes->length > 0) {
                                $style = $nodes[0]->getAttribute("style");
                                //echo "\n" . $style;
                                if (strpos($style, "fill:none") !== false) {
                                    $style = str_replace("fill:none;", "fill:#00FF00; fill-opacity:0.6;", $style);
                                    $node->removeAttribute("style");
                                    $node->setAttribute("style", $style);

                                    echo "\n" . $nodes[0]->getAttribute("style");
                                }

                                $linkNode->appendChild($nodes[0]);
                            }


                            $element->appendChild($linkNode);
                        }

                    }
                }

                $doc->preserveWhiteSpace = false;
                $doc->formatOutput = true;

                file_put_contents($filename . ".xml", $doc->saveXML());

            }
        }
    }

    function findCPTACWikiPathwayCodes($file) {
        $cptac_pathways = [];

        while (! feof($file)) {
            $cptac_pathways[] = fgets($file);
        }

        fclose($file);

        return $cptac_pathways;
    }

    function getPathwayName($words = []) {
        $name = "";
        for ($i = 1; $i < count($words) - 2;  $i++) {
            if (!empty($name)){
                $name = $name . ' ' . $words[$i];
            } else {
                $name = $words[1];
            }
        }

        return $name;
    }

    function setSvgViewBox(&$rootNode = false) {
        $width = $rootNode->getAttribute("width");
        $height = $rootNode->getAttribute("height");

        $rootNode->setAttribute("viewbox", "0 0 ". $width . " " . $height);
        $rootNode->removeAttribute("width");
        $rootNode->removeAttribute("height");
        $rootNode->setAttribute("width", "965");
    }

    function loadSvg($filename = false) {
        $xml = simplexml_load_file($filename);

        $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

        $doc = new DOMDocument('1.0', 'utf-8');

        $doc->loadXML($xml->asXML());

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');

        return $xpath;
    }

    function loadGpml($filename = false) {
        $xml = simplexml_load_file($filename);

        $xml->registerXPathNamespace('Pathway', 'http://pathvisio.org/GPML/2013a');

        $doc = new DOMDocument('1.0', 'utf-8');

        $doc->loadXML($xml->asXML());

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('Pathway', 'http://pathvisio.org/GPML/2013a');

        return $xpath;
    }

    function findWikipathwayCategory($xpath = false) {
        if($xpath) {
            $listNodes = $xpath->query('//Pathway:Comment[contains(@Source, "WikiPathways-category")]');
            if(!empty($listNodes)) {
                return $listNodes[0]->nodeValue;
            }
        }
        return false;
    }

}

?>