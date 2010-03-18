<?php

/*
 * PorPOISe
 * Copyright 2009 SURFnet BV
 * Released under a permissive license (see LICENSE)
 */

/**
 * Classes for Point of Interest definition
 *
 * @package PorPOISe
 */

/**
 * Subclasses of this class can all be converted to (associative) arrays
 * (useful for a.o. JSON-ing).
 *
 * @package PorPOISe
 */
abstract class Arrayable {
	/**
	 * Stores the contents of this object into an associative array
	 * with elements named after the members of the object. Members that
	 * contain properties are converted recursively.
	 *
	 * @return array
	 */
	public function toArray() {
		$result = array();
		$reflectionClass = new ReflectionClass($this);
		$reflectionProperties = $reflectionClass->getProperties();
		foreach ($reflectionProperties as $reflectionProperty) {
			$propertyName = $reflectionProperty->getName();
			$result[$propertyName] = $this->$propertyName;
			if (is_object($result[$propertyName])) {
				$result[$propertyName] = $result[$propertyName]->toArray();
			} else if (is_array($result[$propertyName])) {
				$result[$propertyName] = self::arrayToArray($result[$propertyName]);
			}
		}
		return $result;
	}

	/**
	 * Traverse an array recursively to call toArray on each object
	 *
	 * @return array
	 */
	protected function arrayToArray($array) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = self::arrayToArray($value);
			} else if (is_object($value)) {
				$array[$key] = $value->toArray();
			} else {
				$array[$key] = $value;
			}
		}
		return $array;
	}
}

/**
 * Class to store a POI action
 *
 * @package PorPOISe
 */
class POIAction extends Arrayable {
	/** Default action label. Only for  flat files */
	const DEFAULT_ACTION_LABEL = "Do something funky";

	/** @var string URI that should be invoked by activating this action */
	public $uri = NULL;
	/** @var string Label to show in the interface */
	public $label = NULL;
	
	// do not pre-declare these properties b/c they are optional in the output
	// saves a few bytes in data transport
	/** @var int Range for action autotrigger */
//	public $autoTriggerRange = NULL;
	/** @var bool Only act on autotrigger */
//	public $autoTriggerOnly = FALSE;

	/**
	 * Constructor
	 *
	 * If $source is a string, it must be a URI and a default label will be
	 * assigned to it
	 * If $source is an array it is expected to contain elements "label"
	 * and "uri".
	 * If $source is an object, it is expected to have members "label" and
	 * "uri".
	 *
	 * @param mixed $source
	 */
	public function __construct($source = NULL) {
		if (empty($source)) {
			return;
		}

		if (is_string($source)) {
			$this->label = self::DEFAULT_ACTION_LABEL;
			$this->uri = $source;
		} else if (is_array($source)) {
			$this->label = $source["label"];
			$this->uri = $source["uri"];
			if (!empty($source["autoTriggerRange"])) {
				$this->autoTriggerRange = (int)$source["autoTriggerRange"];
				$this->autoTriggerOnly = (bool)$source["autoTriggerOnly"];
			}
		} else {
			$this->label = (string)$source->label;
			$this->uri = (string)$source->uri;
			if (!empty($source->autoTriggerRange)) {
				$this->autoTriggerRange = (int)$source->autoTriggerRange;
				$this->autoTriggerOnly = (bool)((string)$source->autoTriggerOnly);
			}
		}
	}
}

/**
 * Holds transformation information for multi-dimensional POIs
 *
 * @package PorPOISe
 */
class POITransform extends Arrayable {
	/** @var boolean Specifies whether the POIs position transformation is relative to
	 * the viewer, i.e. always facing the same direction */
	public $rel = FALSE;
	/** @var float Rotation angle in degrees to rotate the object around the z-axis. */
	public $angle = 0;
	/** @var float Scaling factor */
	public $scale = 1;

	/**
	 * Constructor
	 */
	public function __construct($source = NULL) {
		if (empty($source)) {
			return;
		}

		if (is_array($source)) {
			$this->rel = (bool)$source["rel"];
			$this->angle = (float)$source["angle"];
			$this->scale = (float)$source["scale"];
		} else {
			$this->rel = (bool)((string)$source->rel);	/* SimpleXMLElement objects always get cast to TRUE even when representing an empty element */
			$this->angle = (float)$source->angle;
			$this->scale = (float)$source->scale;
		}
	}
}

/**
 * Class for storing 2D/3D object information
 *
 * @package PorPOISe
 */
class POIObject extends Arrayable {
	/** @var string Base URL to resolve all the other references */
	public $baseURL;
	/** @var string Filename of the full object */
	public $full;
	/** @var string Filename of a pre-scaled reduced object */
	public $reduced = NULL;
	/** @var string Filename of an icon of the object for viewing from afar */
	public $icon = NULL;
	/** @var float Size of the object in meters, i.e. the length of the smallest cube that can contain the object */
	public $size;

	/**
	 * Constructor
	 */
	public function __construct($source = NULL) {
		if (empty($source)) {
			return;
		}

		if (is_array($source)) {
			$this->baseURL = $source["baseURL"];
			$this->full = $source["full"];
			if (!empty($source["reduced"])) {
				$this->reduced = $source["reduced"];
			}
			if (!empty($source["icon"])) {
				$this->icon = $source["icon"];
			}
			$this->size = (float)$source["size"];
		} else {
			$this->baseURL = (string)$source->baseURL;
			$this->full = (string)$source->full;
			if (!empty($source->reduced)) {
				$this->reduced = (string)$source->reduced;
			}
			if (!empty($source->icon)) {
				$this->icon = (string)$source->icon;
			}
			$this->size = (float)$source->size;
		}
	}
}

/**
 * Class for storing POI information
 *
 * Subclasses should define a "dimension" property or they will
 * always be interpreted by Layar as 1-dimensional points.
 *
 * @package PorPOISe
 */
abstract class POI extends Arrayable {
	/** @var POIAction[] Possible actions for this POI */
	public $actions = array();
	/** @var string attribution text */
	public $attribution = NULL;
	/** @var int Distance in meters between the user and this POI */
	public $distance = NULL;
	/** @var string Identifier for this POI */
	public $id = NULL;
	/** @var string URL of an image to show for this POI */
	public $imageURL = NULL;
	/** @var int Latitude of this POI in microdegrees */
	public $lat = NULL;
	/** @var int Longitude of this POI in microdegrees */
	public $lon = NULL;
	/** @var string Second line of text */
	public $line2 = NULL;
	/** @var string Third line of text */
	public $line3 = NULL;
	/** @var string Fourth line of text */
	public $line4 = NULL;
	/** @var string Title */
	public $title = NULL;
	/** @var int POI type (for custom icons) */
	public $type = NULL;

	/**
	 * Constructor
	 *
	 * $source is expected to be an array or an object, with element/member
	 * names corresponding to the member names of POI. This allows both
	 * constructing from an associatiev array as well as copy constructing.
	 *
	 * @param mixed $source
	 */
	public function __construct($source = NULL) {
		if (!empty($source)) {
			$reflectionClass = new ReflectionClass($this);
			$reflectionProperties = $reflectionClass->getProperties();
			foreach ($reflectionProperties as $reflectionProperty) {
				$propertyName = $reflectionProperty->getName();
				if (is_array($source)) {
					if (isset($source[$propertyName])) {
						if ($propertyName == "actions") {
							$value = array();
							foreach ($source["actions"] as $sourceAction) {
								$value[] = new POIAction($sourceAction);
							}
						} else if ($propertyName == "object") {
							$value = new POIObject($source["object"]);
						} else if ($propertyName == "transform") {
							$value = new POITransform($source["transform"]);
						} else {
							$value = $source[$propertyName];
						}
						$this->$propertyName = $value;
					}
				} else {
					if (isset($source->$propertyName)) {
						if ($propertyName == "actions") {
							$value = array();
							foreach ($source->actions as $sourceAction) {
								$value[] = new POIAction($sourceAction);
							}
						} else if ($propertyName == "object") {
							$value = new POIObject($source->object);
						} else if ($propertyName == "transform") {
							$value = new POITransform($source->transform);
						} else {
							$value = $source->$propertyName;
						}
						$this->$propertyName = $value;
					}
				}
			}
		}
	}
}

/**
 * Class for storing 1-dimensional POIs
 *
 * @package PorPOISe
 */
class POI1D extends POI {
	/** @var int Number of dimensions for this POI */
	public $dimension = 1;
}

/**
 * Abstract superclass for storing multidimensional POIs
 *
 * @package PorPOISe
 */
abstract class MultidimensionalPOI extends POI {
	/** @var int Altitude of this object in meters. */
	public $alt;
	/** @var POITransform Transformation specification */
	public $transform;
	/** @var POIObject Object specification */
	public $object;
	/** @var int Altitude difference with respect to user's altitude */
	public $relativeAlt;

	/**
	 * Extra constructor
	 */
	public function __construct($source = NULL) {
		parent::__construct($source);
		if (empty($this->transform)) {
			$this->transform = new POITransform();
		}
		if (empty($this->object)) {
			$this->object = new POIObject();
		}
	}
}

/**
 * Class for storing 2D POI information
 *
 * @package PorPOISe
 */
class POI2D extends MultidimensionalPOI {
	/** @var int Number of dimensions for this POI */
	public $dimension = 2;
}

/**
 * Class for storing 3D POI information
 *
 * @package PorPOISe
 */
class POI3D extends MultidimensionalPOI {
	/** @var int Number of dimensions for this POI */
	public $dimension = 3;
}