<?php

/*
 * PorPOISe
 * Copyright 2009 SURFnet BV
 * Released under a permissive license (see LICENSE)
 */

/**
 * PorPOISe dashboard GUI
 *
 * @package PorPOISe
 * @subpackage Dashboard
 */

// use output buffering so we can prevent output of we want to
ob_start(array("GUI", "finalize"));

/**
 * GUI class
 *
 * All methods are static
 *
 * @package PorPOISe
 * @subpackage Dashboard
 */
class GUI {
	/** controls whether the GUI displays developer key */
	const SHOW_DEVELOPER_KEY = TRUE;

	/**
	 * Callback for ob_start()
	 *
	 * Adds header and footer to HTML output and does post-processing
	 * if required
	 *
	 * @param string $output The output in the buffer
	 * @param int $state A bitfield specifying what state the script is in (start, cont, end)
	 *
	 * @return string The new output
	 */
	public static function finalize($output, $state) {
		$result = "";
		if ($state & PHP_OUTPUT_HANDLER_START) {
			$result .= self::createHeader();
		}
		$result .= $output;
		if ($state & PHP_OUTPUT_HANDLER_END) {
			$result .= self::createFooter();
		}
		return $result;
	}

	/**
	 * Print a formatted message
	 *
	 * @param string $message sprintf-formatted message
	 * 
	 * @return void
	 */
	public static function printMessage($message) {
		$args = func_get_args();
		/* remove first argument, which is $message */
		array_splice($args, 0, 1);
		vprintf($message, $args);
	}

	/**
	 * Print an error message
	 *
	 * @param string $message sprintf-formatted message
	 *
	 * @return void
	 */
	public static function printError($message) {
		$args = func_get_args();
		$args[0] = sprintf("<p class=\"error\">%s</p>\n", $args[0]);
		call_user_func_array(array("GUI", "printMessage"), $args);
	}

	/**
	 * Create a header
	 *
	 * @return string
	 */
	public static function createHeader() {
		return
<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>PorPOISe POI Management Interface</title>
<link rel="stylesheet" type="text/css" href="styles.css">
<script type="text/javascript" src="prototype.js"></script>
<script type="text/javascript" src="scripts.js"></script>
</head>
<body>

<div class="menu">
 <a href="?logout=true">Log out</a>
 <a href="?action=main">Home</a>
</div>

<div class="main">
HTML;
	}

	/**
	 * Create a footer
	 *
	 * @return string
	 */
	public static function createFooter() {
		return
<<<HTML
</div> <!-- end main div -->
</body>
</html>
HTML;
	}

	/**
	 * Create a select box
	 *
	 * @param string $name
	 * @param array $options
	 * @param mixed $selected
	 *
	 * @return string
	 */
	protected static function createSelect($name, $options, $selected = NULL) {
		$result = sprintf("<select name=\"%s\">\n", $name);
		foreach ($options as $value => $label) {
			$result .= sprintf("<option value=\"%s\"%s>%s</option>\n", $value, ($value ==  $selected ? " selected" : ""), $label);
		}
		$result .="</select>\n";
		return $result;
	}

	/**
	 * Create "main" screen
	 *
	 * @return string
	 */
	public static function createMainScreen() {
		$result = "";
		$result .= "<p>Welcome to PorPOISe</p>\n";
		$result .= self::createMainConfigurationTable();
		$result .= "<p>Layers:</p>\n";
		$result .= self::createLayerList();
		return $result;
	}

	/**
	 * Create a table displaying current configuration
	 *
	 * @return string
	 */
	public static function createMainConfigurationTable() {
		$config = DML::getConfiguration();
		$result = "";
		$result .= "<table class=\"config\">\n";
		$result .= sprintf("<tr><td>Developer ID</td><td>%s</td></tr>\n", $config->developerID);
		$result .= sprintf("<tr><td>Developer key</td><td>%s</td></tr>\n", (self::SHOW_DEVELOPER_KEY ? $config->developerKey : "&lt;hidden&gt;"));
		$result .= sprintf("</table>\n");
		return $result;
	}

	/**
	 * Create a list of layers
	 *
	 * @return string
	 */
	public static function createLayerList() {
		$config = DML::getConfiguration();
		$result = "";
		$result .= "<ul>\n";
		foreach ($config->layerDefinitions as $layerDefinition) {
			$result .= sprintf("<li><a href=\"%s?action=layer&layerName=%s\">%s</a></li>\n", $_SERVER["PHP_SELF"], $layerDefinition->name, $layerDefinition->name);
		}
		$result .= "</ul>\n";
		return $result;
	}

	/**
	 * Create a screen for viewing/editing a layer
	 *
	 * @param string $layerName
	 *
	 * @return string
	 */
	public static function createLayerScreen($layerName) {
		$layerDefinition = DML::getLayerDefinition($layerName);
		if ($layerDefinition == NULL) {
			throw new Exception(sprintf("Unknown layer: %s\n", $layerName));
		}
		$result = "";
		$result .= sprintf("<p>Layer name: %s</p>\n", $layerName);
		$result .= sprintf("<p>POI collector: %s</p>\n", $layerDefinition->collector);
		$result .= sprintf("<p><a href=\"?action=newPOI&layerName=%s\">New POI</a></p>\n", urlencode($layerName));
		$result .= self::createPOITable($layerName);
		return $result;
	}

	/**
	 * Create a list of POIs for a layer
	 *
	 * @param string $layerName
	 *
	 * @return string
	 */
	public static function createPOITable($layerName) {
		$result = "";
		$pois = DML::getPOIs($layerName);
		if ($pois === NULL || $pois === FALSE) {
			throw new Exception("Error retrieving POIs");
		}
		$result .= "<table class=\"pois\">\n";
		$result .= "<tr><th>Title</th><th>Lat/lon</th></tr>\n";
		foreach ($pois as $poi) {
			$result .= "<tr>\n";
			$result .= sprintf("<td><a href=\"?action=poi&layerName=%s&poiID=%s\">%s</a></td>\n", urlencode($layerName), urlencode($poi->id), ($poi->title ? $poi->title : "&lt;no title&gt;"));
			$result .= sprintf("<td>%s,%s</td>\n", $poi->lat, $poi->lon);
			$result .= sprintf("<td><form action=\"?action=deletePOI\" method=\"POST\"><input type=\"hidden\" name=\"layerName\" value=\"%s\"><input type=\"hidden\" name=\"poiID\" value=\"%s\"><button type=\"submit\">Delete</button></form></td>\n", urlencode($layerName), urlencode($poi->id));
			$result .= "</tr>\n";
		}
		$result .= "</table>\n";
		return $result;
	}

	/**
	 * Create a screen for a single POI
	 *
	 * @param string $layerName
	 * @param string $poi POI to display in form. Leave empty for new POI
	 *
	 * @return string
	 */
	public static function createPOIScreen($layerName, $poi = NULL) {
		if (empty($poi)) {
			$poi = new POI1D();
		}
		$result = "";
		$result .= sprintf("<p><a href=\"?action=layer&layerName=%s\">Back to %s</a></p>\n", urlencode($layerName), $layerName);
		$result .= sprintf("<form action=\"?layerName=%s&action=poi&poiID=%s\" method=\"POST\">\n", urlencode($layerName), urlencode($poi->id));
		$result .= "<table class=\"poi\">\n";
		$result .= sprintf("<tr><td>ID</td><td><input type=\"hidden\" name=\"id\" value=\"%s\">%s</td></tr>\n", $poi->id, $poi->id);
		$result .= sprintf("<tr><td>Title</td><td><input type=\"text\" name=\"title\" value=\"%s\"></td></tr>\n", $poi->title);
		$result .= sprintf("<tr><td>Lat/lon</td><td><input type=\"text\" name=\"lat\" value=\"%s\" size=\"7\"><input type=\"text\" name=\"lon\" value=\"%s\" size=\"7\"></td></tr>\n", $poi->lat, $poi->lon);
		$result .= sprintf("<tr><td>Line 2</td><td><input type=\"text\" name=\"line2\" value=\"%s\"></td></tr>\n", $poi->line2);
		$result .= sprintf("<tr><td>Line 3</td><td><input type=\"text\" name=\"line3\" value=\"%s\"></td></tr>\n", $poi->line3);
		$result .= sprintf("<tr><td>Line 4</td><td><input type=\"text\" name=\"line4\" value=\"%s\"></td></tr>\n", $poi->line4);
		$result .= sprintf("<tr><td>Attribution</td><td><input type=\"text\" name=\"attribution\" value=\"%s\"></td></tr>\n", $poi->attribution);
		$result .= sprintf("<tr><td>Image URL</td><td><input type=\"text\" name=\"imageURL\" value=\"%s\"></td></tr>\n", $poi->imageURL);
		$result .= sprintf("<tr><td>Type</td><td><input type=\"text\" name=\"type\" value=\"%s\" size=\"1\"></td></tr>\n", $poi->type);
		$result .= sprintf("<tr><td>Dimension</td><td><input type=\"text\" name=\"dimension\" value=\"%s\" size=\"1\"></td></tr>\n", $poi->dimension);
		if ($poi->dimension > 1) {
			$result .= sprintf("<tr><td>Absolute altitude</td><td><input type=\"text\" name=\"alt\" value=\"%s\" size=\"2\"></td></tr>\n", $poi->alt);
			$result .= sprintf("<tr><td>Relative altitude</td><td><input type=\"text\" name=\"relativeAlt\" value=\"%s\" size=\"2\"></td></tr>\n", $poi->relativeAlt);
			$result .= sprintf("<tr><td>Base URL for model</td><td><input type=\"text\" name=\"baseURL\" value=\"%s\"></td></tr>\n", $poi->object->baseURL);
			$result .= sprintf("<tr><td>Full model</td><td><input type=\"text\" name=\"full\" value=\"%s\"></td></tr>\n", $poi->object->full);
			$result .= sprintf("<tr><td>Reduced model</td><td><input type=\"text\" name=\"reduced\" value=\"%s\"></td></tr>\n", $poi->object->reduced);
			$result .= sprintf("<tr><td>Model icon</td><td><input type=\"text\" name=\"icon\" value=\"%s\"></td></tr>\n", $poi->object->icon);
			$result .= sprintf("<tr><td>Model size (approx)</td><td><input type=\"text\" name=\"size\" value=\"%s\" size=\"1\"></td></tr>\n", $poi->object->size);
			$result .= sprintf("<tr><td>Scaling factor</td><td><input type=\"text\" name=\"scale\" value=\"%s\" size=\"2\"></td></tr>\n", $poi->transform->scale);
			$result .= sprintf("<tr><td>Vertical rotation</td><td><input type=\"text\" name=\"angle\" value=\"%s\" size=\"1\"></td></tr>\n", $poi->transform->angle);
			$relOptions = array(TRUE => "Yes", FALSE => "No");
			$result .= sprintf("<tr><td>Relative angle</td><td>%s</td></tr>\n", self::createSelect("rel", $relOptions, (bool)$poi->transform->rel));
		}
		foreach ($poi->actions as $key => $action) {
			$result .= sprintf("<tr><td>Action<br><button type=\"button\" onclick=\"GUI.removePOIAction(%s)\">Remove</button></td><td>%s</td></tr>\n", $key, self::createActionSubtable($key, $action));
		}
		$result .= sprintf("<tr><td colspan=\"2\"><button type=\"button\" onclick=\"GUI.addPOIAction(this)\">New action</button></td></tr>\n");

		$result .= "<caption><button type=\"submit\">Save</button></caption>\n";
		$result .= "</table>\n";
		$result .= "</form>";
		return $result;
	}

	/**
	 * Create a subtable for an action for inside a form
	 *
	 * @param string $index Index of the action in the actions[] array
	 * @param POIAction $action The action
	 *
	 * @return string
	 */
	protected static function createActionSubtable($index, POIAction $action) {
		$result = "";
		$result .= "<table class=\"action\">\n";
		$result .= sprintf("<tr><td>Label</td><td><input type=\"text\" name=\"actions[%s][label]\" value=\"%s\"></td></tr>\n", $index, $action->label);
		$result .= sprintf("<tr><td>URI</td><td><input type=\"text\" name=\"actions[%s][uri]\" value=\"%s\"></td></tr>\n", $index, $action->uri);
		$result .= sprintf("<tr><td>Auto-trigger range</td><td><input type=\"text\" name=\"actions[%s][autoTriggerRange]\" value=\"%s\" size=\"2\"></td></tr>\n", $index, $action->autoTriggerRange);
		$result .= sprintf("<tr><td>Auto-trigger only</td><td>%s</td></tr>\n", self::createSelect(sprintf("actions[%s][autoTriggerOnly]", $index), array(TRUE => "Yes", FALSE => "No"), (bool)$action->autoTriggerOnly));
		$result .= "</table>\n";

		return $result;
	}


	/**
	 * Create a screen for a new POI
	 *
	 * @param string $layerName
	 *
	 * @return string
	 */
	public function createNewPOIScreen($layerName) {
		$result = "";
		$result .= sprintf("<form action=\"?action=newPOI&layerName=%s\" method=\"POST\">\n", urlencode($layerName));
		$result .= sprintf("<table class=\"newPOI\">\n");
		$result .= sprintf("<tr><td>Dimension</td><td><input type=\"text\" name=\"dimension\" size=\"1\"></td></tr>\n");
		$result .= sprintf("<caption><button type=\"submit\">Create</button></caption>");
		$result .= "</table>\n";
		$result .= "</form>\n";
		return $result;
	}

	/**
	 * Create login screen
	 *
	 * @return string
	 */
	public static function createLoginScreen() {
		$result = "";
		/* preserve GET parameters */
		$get = $_GET;
		unset($get["username"]);
		unset($get["password"]);
		unset($get["logout"]);
		$getString = "";
		$first = TRUE;
		foreach ($get as $key => $value) {
			if ($first) {
				$first = FALSE;
				$getString .= "?";
			} else {
				$getString .= "&";
			}
			$getString .= urlencode($key) . "=" . urlencode($value);
		}
		$result .= sprintf("<form method=\"POST\" action=\"%s%s\">\n", $_SERVER["PHP_SELF"], $getString);
		$result .= "<table class=\"login\">\n";
		$result .= "<tr><td>Username</td><td><input type=\"text\" name=\"username\" size=\"15\"></td></tr>\n";
		$result .= "<tr><td>Password</td><td><input type=\"password\" name=\"password\" size=\"15\"></td></tr>\n";
		$result .= "<caption><button type=\"submit\">Log in</button></caption>\n";
		$result .= "</table>\n";
		/* preserve POST */
		foreach ($_POST as $key => $value) {
			switch ($key) {
			case "username":
			case "password":
			case "logout":
				break;
			default:
				$result .= sprintf("<input type=\"hidden\" name=\"%s\" value=\"%s\">\n", $key, $value);
				break;
			}
		}

		$result .= "</form>\n";

		return $result;
	}

	/**
	 * Handle POST
	 *
	 * Checks whether there is something in the POST to handle and calls
	 * appropriate methods if there is.
	 *
	 * @throws Exception When invalid data is passed in POST
	 */
	public static function handlePOST () {
		$post = $_POST;
		/* not interested in login attempts */
		unset($post["username"]);
		unset($post["password"]);
		
		if (empty($post)) {
			/* nothing interesting in POST */
			return;
		}
		$action = $_REQUEST["action"];
		switch ($action) {
		case "poi":
			$poi = self::makePOIFromRequest($post);
			DML::savePOI($_REQUEST["layerName"], $poi);
			break;
		case "newPOI":
			$poi = self::makePOIFromRequest($post);
			DML::savePOI($_REQUEST["layerName"], $poi);
			self::redirect("layer", array("layerName" => $_REQUEST["layerName"]));
			break;
		case "deletePOI":
			DML::deletePOI($_REQUEST["layerName"], $_REQUEST["poiID"]);
			self::redirect("layer", array("layerName" => $_REQUEST["layerName"]));
			break;
		default:
			throw new Exception(sprintf("No post handler defined for action %s\n", $action));
		}
	}

	/**
	 * Turn request data into a POI object
	 *
	 * @param array $request The data from the request
	 *
	 * @return POI
	 */
	protected static function makePOIFromRequest($request) {
		switch ($request["dimension"]) {
		case "1":
			$result = new POI1D();
			break;
		case "2":
			$result = new POI2D();
			break;
		case "3":
			$result = new POI3D();
			break;
		default:
			throw new Exception("Invalid dimension: %d\n", $request["dimension"]);
		}

		foreach ($request as $key => $value) {
			switch ($key) {
			case "dimension":
			case "type":
			case "alt":
			case "relativeAlt":
				$result->$key = (int)$request[$key];
				break;
			case "lat":
			case "lon":
				$result->$key = (float)$request[$key];
				break;
			case "baseURL":
			case "full":
			case "reduced":
			case "icon":
				$result->object->$key = (string)$request[$key];
				break;
			case "size":
				$result->object->$key = (int)$request[$key];
				break;
			case "angle":
				$result->transform->$key = (int)$request[$key];
				break;
			case "rel":
				$result->transform->$key = (bool)$request[$key];
				break;
			case "scale":
				$result->transform->$key = (float)$request[$key];
				break;
			case "actions":
				foreach ($value as $action) {
					$result->actions[] = new POIAction($action);
				}
				break;
			default:
				$result->$key = (string)$request[$key];
				break;
			}
		}
		
		return $result;
	}

	/**
	 * Redirect (HTTP 300) user
	 *
	 * This method fails if headers are already sent
	 *
	 * @param string $action New action to go to
	 * @param array $arguments
	 *
	 * @return void On success, does not return but calls exit()
	 */
	protected static function redirect($where, array $arguments = array()) {
		if (headers_sent()) {
			self::printError("Headers are already sent");
			return;
		}
		$getString = "";
		$getString .= sprintf("?action=%s", urlencode($where));
		foreach ($arguments as $key => $value) {
			$getString .= sprintf("&%s=%s", urlencode($key), urlencode($value));
		}
		if (empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] == "off") {
			$scheme = "http";
		} else {
			$scheme = "https";
		}
		$location = sprintf("%s://%s%s%s", $scheme, $_SERVER["HTTP_HOST"], $_SERVER["PHP_SELF"], $getString);
		header("Location: " . $location);
		exit();
	}
			
}