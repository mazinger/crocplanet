<?php

/**
 * Magento Owebia Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Owebia
 * @copyright  Copyright (c) 2008-09 Owebia (http://www.owebia.com)
 * @author     Antoine Lemoine
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class OwebiaShippingHelper
{
	public static $FLOAT_REGEX = '[-]?\d+(?:[.]\d+)?';
	public static $POSITIVE_FLOAT_REGEX = '\d+(?:[.]\d+)?';
	//public static $COUPLE_REGEX = '(?:[0-9.]+|\*)(?:\[|\])?\:[0-9.]+(?:\:[0-9.]+%?)*';
	public static $COUPLE_REGEX = '(?:[0-9.]+|\*)(?:\[|\])?\:[0-9.]+';
	public static $DEBUG = false;

	protected $_messages;
	protected $_formula_cache;
	protected $_expression_cache;

	public function OwebiaShippingHelper()
	{
		$this->_formula_cache = array();
		$this->_messages = array();
	}

	public function getMessages()
	{
		$messages = $this->_messages;
		$this->_messages = array();
		return $messages;
	}

	public function formatConfig($config)
	{
		$output = '';
		foreach ($config as $code => $row)
		{
			if (isset($row['*comment'])) {
				$output .= trim($row['*comment'])."\n";
			}
			$output .= "{\n";
			foreach ($row as $key => $value)
			{
				if (substr($key,0,1)!='*')
				{
					$output .= "\t".$key.": ";
					if (is_bool($value)) $output .= $value ? 'true' : 'false';
					else if (is_int($value)) $output .= $value;
					else if ((string)((float)$value)==$value) $output .= $value;
					else $output .= '"'.str_replace('"','\\"',$value).'"';
					$output .= ",\n";
				}
			}
			$output .= "}\n\n";
		}
		return $output;
	}

	public function checkConfig($config)
	{
		$process = array(
			'result' => null,
			'price_excluding_tax' => 0,
			'price_including_tax' => 0,
			'destination_country_code' => '',
			'destination_region_code' => '',
			'destination_postcode' => '',
			'destination_country_name' => '',
			'origin_country_code' => '',
			'origin_region_code' => '',
			'origin_postcode' => '',
			'origin_country_name' => '',
			'free_shipping' => false,
			'customer_group_id' => '',
			'customer_group_code' => '',
			'package_weight' => 0,
			'weight_unit' => 'kg',
			'products_quantity' => 0,
			'items' => array(),
			'products' => array(),
		);

		foreach ($config as $code => $row)
		{
			$this->processRow($process,$row,$check_all_conditions=true);
			foreach ($row as $property_key => $property_value)
			{
				if (substr($property_key,0,1)!='*') $this->getRowProperty($config,$row,$property_key);
			}
		}
	}

	public function getFormattedMessages($language)
	{
		$output = '';
		
		$translations = array(
			'fr' => array(
				"No match found" => "Aucune correspondance trouvée",
				"[%s] Configuration disabled" => "[%s] Configuration désactivée",
				"[%s] The cart doesn't match conditions" => "[%s] Le panier ne correspond pas aux conditions",
				"[%s] The shipping method doesn't cover the zone (%s)" => "[%s] Le mode de livraison ne couvre pas la zone (%s)",
				"[%s] The shipping method doesn't match to shipping origin (%s)" => "[%s] Le mode de livraison ne correspond pas à l'origine de livraison (%s)",
				"[%s] The shipping method doesn't match to customer group (%s)" => "[%s] Le mode de livraison ne correspond pas au groupe client (%s)",
				"There are unwanted spaces after char `%s` in property `%s` %s" => "Il y a des espaces indésirables après le caractère `%s` dans la propriété `%s` %s",
				"There are unwanted spaces before char `%s` in property `%s` %s" => "Il y a des espaces indésirables avant le caractère `%s` dans la propriété `%s` %s",
				"There are multiples spaces in property `%s` %s" => "Il y a des espaces indésirables multiples dans la propriété `%s` %s",
				"[%s.%s] Infinite loop" => "[%s.%s] Boucle infinie",
				"Non-existent property %s" => "Propriété inexistante %s",
				"Error in table %s" => "Erreur dans la table %s",
				"Invalid formula %s Result is %s" => "Formule invalide %s Résultat %s",
				"Unrecognized value of deprecated property `%s` [%s]" => "Valeur non reconnue de la propriété obsolète `%s` [%s]",
				"Usage of deprecated syntax `%s` in property `%s` %s" => "Utilisation d'une syntaxe obsolète `%s` dans la propriété `%s` %s",
				"Usage of unknown properties %s" => "Utilisation de propriétés non reconnues %s",
				"Usage of deprecated properties %s" => "Utilisation de propriétés obsolètes %s",
				"Usage of obsolete method to disabling a shipping method (`#` before `{`)%s" => "Utilisation d'une méthode obsolète pour désactiver une méthode de livraison (`#` avant `{`)%s",
				"A semicolon is missing at the end of following lines %s" => "Il manque une virgule à la fin des lignes suivantes %s",
				"Ignored lines %s" => "Lignes ignorées %s",
				"The property `code` must be unique, `%s` has been found twice" => "La propriété `code` doit être unique, la valeur `%s` a été trouvée plusieurs fois",
			),
		);
		foreach ($this->_messages as $message)
		{
			if (isset($translations[$language][$message->message])) $message->message = $translations[$language][$message->message];
			$output .= "<div class=\"".$message->type."\">".(count($message->args)==0 ? $message->message : vsprintf($message->message,$message->args))."</div>";
		}
		$this->_messages = array();
		return $output;
	}

	public function processRow($process, $row, $is_checking=false)
	{
		if (!isset($row['label'])) $row['label'] = '***';
		$config = $process['config'];
		
		$enabled = $this->getRowProperty($config,$row,'enabled');
		if (isset($enabled))
		{
			if (!$is_checking && !$enabled)
			{
				$this->_messages[] = new OS_Message('info','[%s] Configuration disabled',$row['*code']);
				return new OS_Result(false);
			}
		}

		$conditions = $this->getRowProperty($config,$row,'conditions');
		if (isset($conditions))
		{
			$result = $this->_processFormula($process,$conditions,$row['*debug'],$is_checking);
			if (!$is_checking)
			{
				if (!$result->success) return $result;
				if (!$result->result) {
					$this->_messages[] = new OS_Message('info',"[%s] The cart doesn't match conditions",$row['*code']);
					return new OS_Result(false);
				}
			}
		}

		$destination = $this->getRowProperty($config,$row,'destination');
		if (isset($destination))
		{
			$destination_match = $this->_addressMatch($destination,array(
				'country_code' => $process['destination_country_code'],
				'region_code' => $process['destination_region_code'],
				'postcode' => $process['destination_postcode']
			));
			if (!$is_checking && !$destination_match)
			{
				$this->_messages[] = new OS_Message('info',"[%s] The shipping method doesn't cover the zone (%s)",$row['*code'],$process['destination_country_name']);
				return new OS_Result(false);
			}
		}

		$origin = $this->getRowProperty($config,$row,'origin');
		if (isset($origin))
		{
			$origin_match = $this->_addressMatch($origin,array(
				'country_code' => $process['origin_country_code'],
				'region_code' => $process['origin_region_code'],
				'postcode' => $process['origin_postcode']
			));
			if (!$is_checking && !$origin_match)
			{
				$this->_messages[] = new OS_Message('info',"[%s] The shipping method doesn't match to shipping origin (%s)",$row['*code'],$process['origin_country_name']);
				return new OS_Result(false);
			}
		}

		$customer_groups = $this->getRowProperty($config,$row,'customer_groups');
		if (isset($customer_groups))
		{
			$groups = explode(',',$customer_groups);
			$group_match = false;
			foreach ($groups as $group)
			{
				$group = trim($group);
				if ($group==$process['customer_group_code'] || is_int($group) && $group==$process['customer_group_id'] || $group=='*')
				{
					$group_match = true;
					break;
				}
			}
			if (!$is_checking && !$group_match)
			{
				$this->_messages[] = new OS_Message('info',"[%s] The shipping method doesn't match to customer group (%s)",$row['*code'],$process['customer_group_code']);
				return new OS_Result(false);
			}
		}

		$fees = $this->getRowProperty($config,$row,'fees');
		if (isset($fees))
		{
			$result = $this->_processFormula($process,$fees,$row['*debug'],$is_checking);
			if (!$result->success) return $result;
			return new OS_Result(true,(float)$result->result);
		}
		return new OS_Result(false);
	}

	public function getRowProperty($config, $row, $property_key)
	{
		if (isset($row[$property_key])) {
			$property = $row[$property_key];
			while (/*preg_match('/{copy \'([^\']+)\'\.\'([^\']+)\'}/',$property,$result) || */
				preg_match('/{([a-z0-9_]+)\.([a-z0-9_]+)}/i',$property,$result))
			{
				$ref_code = $result[1];
				$ref_property_key = $result[2];
				if ($ref_code==$row['code'] && $ref_property_key==$property_key)
				{
					$this->_messages[] = new OS_Message('error','[%s.%s] Infinite loop',$ref_code,$property_key);
					return null;
				}
				if (isset($config[$ref_code][$ref_property_key]))
				{
					$replacement = $config[$ref_code][$ref_property_key];
				}
				else
				{
					$this->_messages[] = new OS_Message('error','Non-existent property %s',"<div class=\"code\">".$result[0]."</div>");
					$replacement = 'null';
				}
				if (is_bool($replacement)) $replacement = $replacement ? 'true' : 'false';
				$property = str_replace($result[0],$replacement,$property);
			}
			if (self::$DEBUG || $row['*debug']) echo '[OwebiaShippingHelper.getRowProperty(), '.$row['*code'].', '.$property_key.'] '.$row[$property_key].' => '.$property.'<br />';
			return $property;
		}
		return null;
	}

	protected function _min()
	{
		$args = func_get_args();
		$min = null;
		foreach ($args as $arg)
		{
			if (isset($arg) && (!isset($min) || $min>$arg)) $min = $arg;
		}
		return $min;
	}

	protected function _max()
	{
		$args = func_get_args();
		$max = null;
		foreach ($args as $arg)
		{
			if (isset($arg) && (!isset($max) || $max<$arg)) $max = $arg;
		}
		return $max;
	}

	protected function _processFormula($process, $formula_string, $debug, $is_checking)
	{
		if (self::$DEBUG || $debug) echo '[OwebiaShippingHelper._processFormula(), '.$formula_string.']<br />';
		if (isset($this->_formula_cache[$formula_string])) {
			$result = $this->_formula_cache[$formula_string];
			if (self::$DEBUG || $debug) echo "<pre>get cached formula : ".$formula_string." => ".$result->result."</pre>";
			return $result;
		}
	
		$formula = $formula_string;
		if (self::$DEBUG || $debug) echo "<pre>formula : ".$formula."</pre>";

		$formula = str_replace(
			array('{weight}','{price_including_tax}','{price_excluding_tax}','{products_quantity}',
				'{free_shipping}',"\n","\r","\t"),
			array($process['package_weight'],$process['price_including_tax'],$process['price_excluding_tax'],$process['products_quantity'],
				$process['free_shipping']?'true':'false','','',''),
			$formula
		);
		if (self::$DEBUG || $debug) echo "<pre>formula : ".$formula."</pre>";
		
		//while (preg_match("/{(count|all|any) (attribute|option) '([^'\)]+)' ?(==|<=|>=|<|>|!=) ?(".self::$FLOAT_REGEX."|true|false|'[^'\)]*')}/",$formula,$result)
		//			|| preg_match("/{(sum|count distinct) (attribute|option) '([^'\)]+)'}/",$formula,$result))
		while (preg_match("/{(COUNT) products(?: WHERE ([^}]+))?}/i",$formula,$result)
					|| preg_match("/{(SUM|COUNT DISTINCT) product\.(attributes|options)\.([a-z0-9_]+)(?: WHERE ([^}]+))?}/i",$formula,$result))
		{
			if (isset($this->_expression_cache[$result[0]])) {
				$value = $this->_expression_cache[$result[0]];
				if (self::$DEBUG || $debug) echo "<pre>get cached expression : ".$result[0]." => ".$value."</pre>";
			}
			else {
				$value = $this->_processProductProperty($process['products'],$result,$debug);
				$this->_expression_cache[$result[0]] = $value;
			}
			$formula = str_replace($result[0],$value,$formula);
			if (self::$DEBUG || $debug) echo "<pre>formula : ".$formula."</pre>";
		}
		
		//while (preg_match("/{table '([^']+)' ([^}]+)}/",$formula,$result))
		while (preg_match("/{TABLE ([^}]+) IN ([0-9\.:,\*\[\] ]+)}/i",$formula,$result))
		{
			if (isset($this->_expression_cache[$result[0]])) {
				$value = $this->_expression_cache[$result[0]];
				if (self::$DEBUG || $debug) echo "<pre>get cached expression : ".$result[0]." => ".$value."</pre>";
			}
			else {
				$reference_value = $this->_evalFormula($result[1]);
				if (isset($reference_value)) {
					$fees_table_string = $result[2];
					
					if (!preg_match('#^'.self::$COUPLE_REGEX.'(?:, *'.self::$COUPLE_REGEX.')*$#',$fees_table_string))
					{
						$this->_messages[] = new OS_Message('error','Error in table %s',"<div class=\"code\">".htmlentities($result[0])."</div>");
						$result = new OS_Result(false);
						$this->_formula_cache[$formula_string] = $result;
						return $result;
					}
					$fees_table = explode(',',$fees_table_string);
					
					$value = null;
					foreach ($fees_table as $item)
					{
						$fee_data = explode(':',$item);

						/*$process['empty_package_weight'] = isset($fee_data[2]) ?
							($fee_data[2]{strlen($fee_data[2])-1}=='%' ? substr($fee_data[2],0,strlen($fee_data[2])-1)*$process['package_weight']/100. : $fee_data[2])
							: 0;
						$process['full_weight'] = $process['package_weight']+$process['empty_package_weight'];*/
			
						$fee = trim($fee_data[1]);
						$max_value = trim($fee_data[0]);

						$last_char = $max_value{strlen($max_value)-1};
						if ($last_char=='[') $including_max_value = false;
						else if ($last_char==']') $including_max_value = true;
						else $including_max_value = true;

						$max_value = str_replace(array('[',']'),'',$max_value);

						if ($max_value=='*' || $including_max_value && $reference_value<=$max_value || !$including_max_value && $reference_value<$max_value)
						{
							$value = $fee;//$this->_calculateFee($process,$fee,$var);
							break;
						}
					}
				}
				
				if (!isset($value)) $value = 'null';
				$this->_expression_cache[$result[0]] = $value;
			}
			$formula = str_replace($result[0],$value,$formula);
			if (self::$DEBUG || $debug) echo "<pre>formula : ".$formula."</pre>";
		}
		$eval_result = $this->_evalFormula($formula);
		if (!isset($eval_result))
		{
			$this->_messages[] = new OS_Message('error','Invalid formula %s Result is %s',
				"<div class=\"code\">".htmlentities($formula_string)."</div>","<div class=\"code\">".htmlentities($formula)."</div>");
			$result = new OS_Result(false);
			$this->_formula_cache[$formula_string] = $result;
			return $result;
		}
		//if (true) echo '<!-- '.$formula."  === ".$eval_result."\n-->";
		if (self::$DEBUG || $debug) echo "<pre>formula : ".(is_bool($eval_result) ? ($eval_result ? 'true' : 'false') : $eval_result)."</pre>";
		$result = new OS_Result(true,$eval_result);
		$this->_formula_cache[$formula_string] = $result;
		return $result;
	}

	protected function _evalFormula($formula)
	{
		if (!preg_match('/^(floor|ceil|round|max|min|rand|pow|pi|sqrt|log|exp|abs|int|float|true|false|null|and|or|in|\'[^\']*\'|[0-9,\'\.\-\(\)\*\/\?\:\+\<\>\=\&\|%! ])*$/',$formula)) return null;
		$formula = str_replace(
			array('min','max'),
			array('$this->_min','$this->_max'),
			$formula
		);
		$eval_result = null;
		@eval('$eval_result = ('.$formula.');');
		return $eval_result;
	}

	protected function _getOptionsAndData($string)
	{
		if (preg_match('/^(\\s*\(\\s*([^\] ]*)\\s*\)\\s*)/',$string,$result))
		{
			$options = $result[2];
			$data = str_replace($result[1],'',$string);
		}
		else
		{
			$options = '';
			$data = $string;
		}
		return array(
			'options' => $options,
			'data' => $data,
		);
	}

	public function parseConfig($config_string, $is_checking=false)
	{
		$config_string = str_replace(array('&gt;','&lt;',utf8_encode(chr(147)),utf8_encode(chr(148)),'&laquo;','&raquo;'),array('>','<','"','"','"','"','"','"'),$config_string);
	
		$row_regex = '[ \\t]*([a-z0-9_]+)\\s*:\\s*("(?:(?:[^"]|\\\\")*[^\\\\])?"|'.self::$FLOAT_REGEX.'|false|true)\\s*(,)?[ \\t]*(?:\\r?\\n)?';
		preg_match_all('/((?:#+[^{\\n]+\\s+)*)\\s*(#)?{\\s*('.$row_regex.')+\\s*}/i',$config_string,$result,PREG_SET_ORDER);

		$config = array();
		$errors = array();
		$deprecated_properties = array();
		$unknown_properties = array();
		$missing_semicolon = array();
		$obsolete_disabling_method = array();
		$available_keys = array(
			'code','label','enabled','debug','description','fees','conditions','destination','origin','customer_groups','tracking_url',
			'fees_table','fees_formula','fixed_fees',
			'prices_range','weights_range','product_properties',
			'free_shipping__fees_table','free_shipping__fees_formula','free_shipping__fixed_fees','free_shipping__label',
		);
		
		foreach ($result as $block)
		{
			$config_string = str_replace($block[0], '', $config_string);
			preg_match_all('/'.$row_regex.'/i',$block[0],$result2,PREG_SET_ORDER);
			$block_string = $block[0];

			$row = array();
			$i = 1;
			foreach ($result2 as $data)
			{
				$key = $data[1];
				if (in_array($key,$available_keys) || substr($key,0,1)=='_')
				{
					$row[$key] = $data[2]==='false' || $data[2]==='true' ? $data[2]=='true' : str_replace('\"','"',preg_replace('/^(?:"|\')(.*)(?:"|\')$/s','$1',$data[2]));
					
					if ($row[$key]==='') unset($row[$key]);
					else {
						$regex1 = "{copy '([a-zA-Z0-9_]+)'\.'([a-zA-Z0-9_]+)'}";
						if (preg_match('/'.$regex1.'/',$row[$key],$resi)) {
							$this->_messages[] = new OS_Message('warning','Usage of deprecated syntax `%s` in property `%s` %s',$resi[0],$key,'<div class="code">'.htmlentities($row[$key]).'</div>');
							while (preg_match('/'.$regex1.'/',$row[$key],$resi)) $row[$key] = str_replace($resi[0],'{'.$resi[1].'.'.$resi[2].'}',$row[$key]);
						}

						$regex1 = "{(count|all|any) (attribute|option) '([^'\)]+)' ?((?:==|<=|>=|<|>|!=) ?(?:".self::$FLOAT_REGEX."|true|false|'[^'\)]*'))}";
						$regex2 = "{(sum) (attribute|option) '([^'\)]+)'}";
						if (preg_match('/'.$regex1.'/',$row[$key],$resi) || preg_match('/'.$regex2.'/',$row[$key],$resi)) {
							$this->_messages[] = new OS_Message('warning','Usage of deprecated syntax `%s` in property `%s` %s',$resi[0],$key,'<div class="code">'.htmlentities($row[$key]).'</div>');
							while (preg_match('/'.$regex1.'/',$row[$key],$resi) || preg_match('/'.$regex2.'/',$row[$key],$resi))
							{
								switch ($resi[1]) {
									case 'count':
										$row[$key] = str_replace($resi[0],"{COUNT products WHERE product.".$resi[2]."s.".$resi[3].$resi[4]."}",$row[$key]);
										break;
									case 'all':
										$row[$key] = str_replace($resi[0],"{COUNT products WHERE product.".$resi[2]."s.".$resi[3].$resi[4]."}=={products_quantity}",$row[$key]);
										break;
									case 'any':
										$row[$key] = str_replace($resi[0],"{COUNT products WHERE product.".$resi[2]."s.".$resi[3].$resi[4]."}>0",$row[$key]);
										break;
									case 'sum':
										$row[$key] = str_replace($resi[0],"{SUM product.".$resi[2]."s.".$resi[3]."}",$row[$key]);
										break;
								}
							}
						}

						$regex = "{table '([^']+)' (".self::$COUPLE_REGEX."(?:, *".self::$COUPLE_REGEX.")*)}";
						if (preg_match('/'.$regex.'/',$row[$key],$resi)) {
							$this->_messages[] = new OS_Message('warning','Usage of deprecated syntax `%s` in property `%s` %s',$resi[0],$key,'<div class="code">'.htmlentities($row[$key]).'</div>');
							while (preg_match('/'.$regex.'/',$row[$key],$resi))
							{
								switch ($resi[1]) {
									case 'products_quantity':
										$row[$key] = str_replace($resi[0],"{TABLE {weight} IN ".$resi[2]."}*{products_quantity}",$row[$key]);
										break;
									default:
										$row[$key] = str_replace($resi[0],"{TABLE {".$resi[1]."} IN ".$resi[2]."}",$row[$key]);
										break;
								}
							}
						}
					}
					if ($i>2) {
						$block_string = str_replace($data[0],$i==3 ? "...\n" : '',$block_string);
					}
					if (!isset($data[3]) || $data[3]!=',') {
						if (preg_match('/^("|\')(.{40})(.*)("|\')$/s',$data[2],$resultx))
							$missing_semicolon[] = trim(str_replace($data[2],$resultx[1].$resultx[2].' ...'.$resultx[4],$data[0]));
						else $missing_semicolon[] = trim($data[0]);
					}
				}
				else
				{
					if (!in_array($key,$unknown_properties)) $unknown_properties[] = $key;
				}
				$i++;
			}
			$row['*debug'] = isset($row['debug']) ? $row['debug']==='true' : false;
			if ($block[1]!='') $row['*comment'] = $block[1];
			if ($block[2]=='#' && !isset($row['enabled'])) {
				$row['enabled'] = false;
				$obsolete_disabling_method[] = $block_string;
			}

			$formula_fields_to_check = array();
			if (isset($row['conditions'])) $formula_fields_to_check[] = 'conditions';
			if (isset($row['fees'])) $formula_fields_to_check[] = 'fees';
			
			if (count($formula_fields_to_check)>0)
			{
				foreach ($formula_fields_to_check as $property) {
					$property_value = $row[$property];
					if (preg_match('/{ +/',$property_value)) {
						$this->_messages[] = new OS_Message('warning','There are unwanted spaces after char `%s` in property `%s` %s','{',$property,'<div class="code">'.htmlentities($row[$property]).'</div>');
						$property_value = preg_replace('/{ +/','{',$property_value);
					}
					if (preg_match('/ +}/',$property_value)) {
						$this->_messages[] = new OS_Message('warning','There are unwanted spaces before char `%s` in property `%s` %s','}',$property,'<div class="code">'.htmlentities($row[$property]).'</div>');
						$property_value = preg_replace('/ +}/','}',$property_value);
					}
					if (preg_match('/  +/',$row[$property])) {
						$this->_messages[] = new OS_Message('warning','There are multiples spaces in property `%s` %s',$property,'<div class="code">'.htmlentities($row[$property]).'</div>');
						$property_value = preg_replace('/  +/',' ',$property_value);
					}
					$row[$property] = trim($property_value);
				}
			}

			$float_value_regex = '\\s*('.self::$POSITIVE_FLOAT_REGEX.'|\*)\\s*';
			$conditions = array();
			if (isset($row['prices_range'])) {
				if (!in_array('prices_range',$deprecated_properties)) $deprecated_properties[] = 'prices_range';

				$result = $this->_getOptionsAndData($row['prices_range']);
				$options = $result['options'];
				$prices_range = $result['data'];

				if (($options=='' || in_array($options,array('incl.tax','ttc')))
					&& preg_match('/^\\s*(\[|\])?'.$float_value_regex.'=>'.$float_value_regex.'(\[|\])?\\s*$/',$prices_range,$result))
				{
					$min_price_included = $result[1]=='[';
					$min_price = $result[2]=='*' ? -1 : (float)$result[2];
					$max_price = $result[3]=='*' ? -1 : (float)$result[3];
					$max_price_included = !isset($result[4]) || $result[4]==']' || $result[4]=='';

					$tax_included = $options!='' && in_array($options,array('incl.tax','ttc')) || isset($row['reference_value']) && $row['reference_value']=='price_including_tax';
					$price = $tax_included ? '{price_including_tax}' : '{price_excluding_tax}';

					if ($min_price!=-1) $conditions[] = $price.'>'.($min_price_included ? '=' : '').$min_price;
					if ($max_price!=-1) $conditions[] = $price.'<'.($max_price_included ? '=' : '').$max_price;
				}
				else $this->_messages[] = new OS_Message('error','Unrecognized value of deprecated property `%s` [%s]','prices_range',$row['prices_range']);
				unset($row['prices_range']);
			}
			if (isset($row['weights_range'])) {
				if (!in_array('weights_range',$deprecated_properties)) $deprecated_properties[] = 'weights_range';
				if (preg_match('/^\\s*(\[|\])?'.$float_value_regex.'=>'.$float_value_regex.'(\[|\])?\\s*$/',$row['weights_range'],$result))
				{
					$min_weight_included = $result[1]=='[';
					$min_weight = $result[2]=='*' ? -1 : (float)$result[2];
					$max_weight = $result[3]=='*' ? -1 : (float)$result[3];
					$max_weight_included = !isset($result[4]) || $result[4]==']' || $result[4]=='';

					if ($min_weight!=-1) $conditions[] = '{weight}>'.($min_weight_included ? '=' : '').$min_weight;
					if ($max_weight!=-1) $conditions[] = '{weight}<'.($max_weight_included ? '=' : '').$max_weight;
				}
				else $this->_messages[] = new OS_Message('error','Unrecognized value of deprecated property `%s` [%s]','weights_range',$row['weights_range']);
				unset($row['weights_range']);
			}
			if (isset($row['product_properties'])) {
				if (!in_array('product_properties',$deprecated_properties)) $deprecated_properties[] = 'product_properties';
				$product_property_regex = "\\s*(and|or)? *\((?:(all|any|sum) )?(attribute|option) '([^'\)]+)' ?(==|=|<=|>=|<|>|!=) ?(".self::$FLOAT_REGEX."|true|false|'[^'\)]*')\)\\s*";
				if (preg_match('/^('.$product_property_regex.')+$/',$row['product_properties'],$result))
				{
					preg_match_all('/'.$product_property_regex.'/',$row['product_properties'],$results,PREG_SET_ORDER);
					$product_properties_condition = '';
					foreach ($results as $result)
					{
						$and_or = $result[1];
						if ($and_or=='') $and_or = 'and';
						$any_all_sum = $result[2];
						if ($any_all_sum=='') $any_all_sum = 'any';
						$property_type = $result[3];
						$property_name = $result[4];
						$cmp_symbol = $result[5];
						if ($cmp_symbol=='=') $cmp_symbol = '==';
						$cmp_value = $result[6];

						$product_properties_condition .= $product_properties_condition=='' ? '' : ' '.$and_or.' ';
						switch ($any_all_sum)
						{
							case 'sum':
								$product_properties_condition .= "{SUM product.".$property_type."s.".$property_name."}".$cmp_symbol.$cmp_value;
								break;
							case 'all':
								$product_properties_condition .= "{COUNT products WHERE product.".$property_type."s.".$property_name.$cmp_symbol.$cmp_value."}=={products_quantity}";
								break;
							case 'any':
								$product_properties_condition .= "{COUNT products WHERE product.".$property_type."s.".$property_name.$cmp_symbol.$cmp_value."}>0";
								break;
						}
					}
					if ($product_properties_condition!='') $conditions[] = $product_properties_condition;
				}
				else $this->_messages[] = new OS_Message('error','Unrecognized value of deprecated property `%s` [%s]','product_properties',$row['product_properties']);
				unset($row['product_properties']);
			}
			if (count($conditions)>0) $row['conditions'] = count($conditions)==1 ? $conditions[0] : '('.implode(') and (',$conditions).')';

			$fees = array();
			if (isset($row['fees_table'])) {
				if (!in_array('fees_table',$deprecated_properties)) $deprecated_properties[] = 'fees_table';
				$options_and_data = $this->_getOptionsAndData($row['fees_table']);
				$options = $options_and_data['options'];
				$fees_table_string = $options_and_data['data'];
				
				$var = null;
				if ($options=='') $var = isset($row['reference_value']) ? $row['reference_value'] : 'weight';
				else if (in_array($options,array('incl.tax','ttc'))) $var = 'price_including_tax';
				
				if (isset($var)) {
					if ($var=='price') $var = 'price_excluding_tax';
					if (preg_match('/^[[:space:]]*\*[[:space:]]*:[[:space:]]*('.$float_value_regex.')[[:space:]]*$/s',$fees_table_string,$result)) $fees[] = $result[1];
					else $fees[] = "{TABLE {".$var."} IN ".str_replace(' ','',$fees_table_string)."}".($var=='products_quantity' ? '*{products_quantity}' : '');
				}
				else $this->_messages[] = new OS_Message('error','Unrecognized value of deprecated property `%s` [%s]','fees_table',$row['fees_table']);
				unset($row['fees_table']);
			}
			if (isset($row['fees_formula'])) {
				if (!in_array('fees_formula',$deprecated_properties)) $deprecated_properties[] = 'fees_formula';
				$fees[] = str_replace(' ','',$row['fees_formula']);
				unset($row['fees_formula']);
			}
			if (isset($row['fixed_fees'])) {
				if (!in_array('fixed_fees',$deprecated_properties)) $deprecated_properties[] = 'fixed_fees';
				if ($row['fixed_fees']!=0 || count($fees)==0) $fees[] = str_replace(' ','',$row['fixed_fees']);
				unset($row['fixed_fees']);
			}
			if (!isset($row['fees']) && count($fees)>0) $row['fees'] = implode('+',$fees);

			$fs_fees = array();
			if (isset($row['free_shipping__fees_table'])) {
				if (!in_array('free_shipping__fees_table',$deprecated_properties)) $deprecated_properties[] = 'free_shipping__fees_table';
				$options_and_data = $this->_getOptionsAndData($row['free_shipping__fees_table']);
				$options = $options_and_data['options'];
				$fees_table_string = $options_and_data['data'];
				
				$var = null;
				if ($options=='') $var = isset($row['reference_value']) ? $row['reference_value'] : 'weight';
				else if (in_array($options,array('incl.tax','ttc'))) $var = 'price_including_tax';
				if ($var=='price') $var = 'price_excluding_tax';

				if (isset($var)) {
					if ($var=='price') $var = 'price_excluding_tax';
					if (preg_match('/^[[:space:]]*\*[[:space:]]*:[[:space:]]*('.$float_value_regex.')[[:space:]]*$/s',$fees_table_string,$result)) $fs_fees[] = $result[1];
					else $fs_fees[] = "{TABLE {".$var."} IN ".str_replace(' ','',$fees_table_string)."}".($var=='products_quantity' ? '*{products_quantity}' : '');
				}
				else $this->_messages[] = new OS_Message('error','Unrecognized value of deprecated property `%s` [%s]','free_shipping__fees_table',$row['free_shipping__fees_table']);
				unset($row['free_shipping__fees_table']);
			}
			if (isset($row['free_shipping__fees_formula'])) {
				if (!in_array('free_shipping__fees_formula',$deprecated_properties)) $deprecated_properties[] = 'free_shipping__fees_formula';
				$fs_fees[] = str_replace(' ','',$row['free_shipping__fees_formula']);
				unset($row['free_shipping__fees_formula']);
			}
			if (isset($row['free_shipping__fixed_fees'])) {
				if (!in_array('free_shipping__fixed_fees',$deprecated_properties)) $deprecated_properties[] = 'free_shipping__fixed_fees';
				if ($row['free_shipping__fixed_fees']!=0 || count($fees)==0) $fs_fees[] = str_replace(' ','',$row['free_shipping__fixed_fees']);
				unset($row['free_shipping__fixed_fees']);
			}

			if (isset($row['reference_value'])) {
				if (!in_array('reference_value',$deprecated_properties)) $deprecated_properties[] = 'reference_value';
				unset($row['reference_value']);
			}

			if (count($fs_fees)>0) {
				$row2 = $row;
				if (isset($row['code'])) $row2['code'] = $row['code'].'__free_shipping';
				$row2['fees'] = implode('+',$fs_fees);
				$row2['conditions'] = isset($row2['conditions']) ? '('.$row2['conditions']+') and {free_shipping}' : '{free_shipping}';
				$row['conditions'] = isset($row['conditions']) ? '('.$row['conditions']+') and !{free_shipping}' : '!{free_shipping}';
				if (isset($row['free_shipping__label'])) {
					if (!in_array('free_shipping__label',$deprecated_properties)) $deprecated_properties[] = 'free_shipping__label';
					$row2['label'] = $row['free_shipping__label'];
					unset($row['free_shipping__label']);
					unset($row2['free_shipping__label']);
				}
				$this->_addRow($config,$row2);
			}
			$this->_addRow($config,$row);
		}
		if (count($unknown_properties)>0) $this->_messages[] = new OS_Message('error','Usage of unknown properties %s','<br />- `'.implode('`<br />- `',$unknown_properties).'`');
		if (count($deprecated_properties)>0) $this->_messages[] = new OS_Message('warning','Usage of deprecated properties %s','<br />- `'.implode('`<br />- `',$deprecated_properties).'`');
		if (count($obsolete_disabling_method)>0) $this->_messages[] = new OS_Message('warning','Usage of obsolete method to disabling a shipping method (`#` before `{`)%s','<div class="code">'.implode('<br />',$obsolete_disabling_method).'</div>');
		if (count($missing_semicolon)>0) $this->_messages[] = new OS_Message('warning','A semicolon is missing at the end of following lines %s','<div class="code">'.implode('<br />',$missing_semicolon).'</div>');
		if (trim($config_string)!='') $this->_messages[] = new OS_Message('info','Ignored lines %s','<div class="code">'.$config_string.'</div>');
		//print_r($errors);
		//print_r($config);
		return $config;
	}
	
	protected function _addRow(&$config, $row)
	{
		if (isset($row['code'])) {
			$key = $row['code'];
			if (isset($config[$key])) $this->_messages[] = new OS_Message('error','The property `code` must be unique, `%s` has been found twice',$key);
			while (isset($config[$key])) $key .= rand(0,9);
			$row['code'] = $key;
		}
		else {
			$key = crc32(serialize($row));
			while (isset($config[$key])) $key .= rand(0,9);
		}
		$row['*code'] = $key;
		$config[$key] = $row;
	}

	protected function _addressMatch($address_filter, $address)
	{
		$excluding = false;
		if (preg_match('# *\* *- *\((.*)\) *#s',$address_filter,$result))
		{
			$address_filter = $result[1];
			$excluding = true;
		}

		$tmp_address_filter_array = explode(',',trim($address_filter));
		
		$concat = false;
		$concatened = '';
		$address_filter_array = array();
		$i = 0;
		
		foreach ($tmp_address_filter_array as $address_filter)
		{
			if ($concat) $concatened .= ','.$address_filter;
			else
			{
				if ($i<count($tmp_address_filter_array)-1 && preg_match('#\(#',$address_filter))
				{
					$concat = true;
					$concatened .= $address_filter;
				}
				else $address_filter_array[] = $address_filter;
			}
			if (preg_match('#\)#',$address_filter))
			{
				$address_filter_array[] = $concatened;
				$concatened = '';
				$concat = false;
			}
			$i++;
		}
		
		foreach ($address_filter_array as $address_filter)
		{
			if (preg_match('# *([A-Z]{2}) *(-)? *(?:\( *(-)? *(.*)\))? *#s',$address_filter,$result))
			{
				$country_code = $result[1];
				if ($address['country_code']==$country_code)
				{
					if (!isset($result[4]) || $result[4]=='') { return !$excluding; }
					else
					{
						$region_codes = explode(',',$result[4]);
						for ($i=count($region_codes); --$i>=0;)
						{
							$region_codes[$i] = trim($region_codes[$i]);
						}
						// Vérification stricte
						$in_array = in_array($address['region_code'],$region_codes,true) || in_array($address['postcode'],$region_codes,true);
						$excluding_region = $result[2]=='-' || $result[3]=='-';
						if ($excluding_region && !$in_array || !$excluding_region && $in_array) return !$excluding;
					}
				}
			}
		}
		return $excluding;
	}
/*
	protected function _test($process, $max_value, $var)
	{
		$last_char = $max_value{strlen($max_value)-1};
		if ($last_char=='[') $including_max_value = false;
		else if ($last_char==']') $including_max_value = true;
		else $including_max_value = true;

		$max_value = str_replace(array('[',']'),'',$max_value);

		$value = 0;
		switch ($var)
		{
			case 'products_quantity':
				$value = $process['products_quantity'];
				break;
			case 'price_excluding_tax':
				$value = $process['price_excluding_tax'];
				break;
			case 'price_including_tax':
				$value = $process['price_including_tax'];
				break;
			case 'weight':
				$value = $process['full_weight'];
				break;
		}
		return ($max_value=='*' || $including_max_value && $value<=$max_value || !$including_max_value && $value<$max_value);
	}

	protected function _calculateFee($process, $fee, $var)
	{
		switch ($var)
		{
			case 'products_quantity':
				return $fee*$process['products_quantity'];
		}
		return $fee;
	}
*/
	protected function _getProductProperty($product, $property_type, $property_name, $get_by_id=false)
	{
		switch ($property_type) {
			case 'attributes' : return $product->getAttribute($property_name,$get_by_id);
			case 'options' : return $product->getOption($property_name,$get_by_id);
		}
		return null;
	}

	protected function _processProductProperty($products, $regex_result, $debug)
	{
		// COUNT, SUM or COUNT DISTINCT
		$operation = strtoupper($regex_result[1]);
		switch ($operation)
		{
			case 'SUM' :
			case 'COUNT DISTINCT' :
				$property_type = $regex_result[2];
				$property_name = $regex_result[3];
				$conditions = isset($regex_result[4]) ? $regex_result[4] : null;
				break;
			case 'COUNT' :
				$conditions = isset($regex_result[2]) ? $regex_result[2] : null;
				break;
		}
		
		$return_value = 0;

		preg_match_all('/product\.(attributes|options)\.([a-z0-9_]+)(?:\.(id))?/i',$conditions,$properties_regex_result,PREG_SET_ORDER);
		$properties = array();
		foreach ($properties_regex_result as $property_regex_result)
		{
			$key = $property_regex_result[0];
			if (!isset($properties[$key])) $properties[$key] = $property_regex_result;
		}

		foreach ($products as $product)
		{
			if (isset($conditions) && $conditions!='')
			{
				$formula = $conditions;
				foreach ($properties as $property)
				{
					$value = $this->_getProductProperty(
						$product,
						$tmp_property_type = $property[1],
						$tmp_property_name = $property[2],
						$get_by_id = isset($property[3]) && $property[3]=='id'
					);
					$formula = str_replace($property[0],is_string($value) || empty($value) ? "'".$value."'" : $value,$formula);
				}
				$eval_result = $this->_evalFormula($formula);
				if (!isset($eval_result)) return 'null';
			}
			else $eval_result = true;

			if ($eval_result==true)
			{
				switch ($operation)
				{
					case 'SUM' :
						$value = $this->_getProductProperty($product,$property_type,$property_name);
						if (self::$DEBUG || $debug) echo $product->getSku().'.'.$property_type.'.'.$property_name.' = "'.$value.'" x '.$product->getQuantity().'<br />';
						$return_value += $value*$product->getQuantity();
						break;
					case 'COUNT DISTINCT' :
						if (!isset($distinct_values)) $distinct_values = array();
						$value = $this->_getProductProperty($product,$property_type,$property_name);
						if (!in_array($value,$distinct_values)) {
							$distinct_values[] = $value;
							$return_value++;
						}
						break;
					case 'COUNT' :
						$return_value += $product->getQuantity();
						break;
				}
			}
		}
		
		return $return_value;
	}
/*
	protected function _processProductProperty($products, $regex_result, $debug)
	{
		$value_type = $regex_result[1];
		$property_type = $regex_result[2];
		$property_name = $regex_result[3];
		if (!in_array($value_type,array('sum','count distinct'))) {
			$compare_symbol = $regex_result[4];
			$get_by_id = preg_match('/^\d*$/',$regex_result[5]);
		
			$compare_value = $regex_result[5];
			$compare_value_replacement = array(
				'true' => 1,
				'false' => 0,
			);
			if (isset($compare_value_replacement[$compare_value])) $compare_value = $compare_value_replacement[$compare_value];
			else $compare_value = preg_replace("/^'(.*)'$/s",'$1',$compare_value);
		} else {
			$get_by_id = false;
		}

		$return_value = 0;

		foreach ($products as $product)
		{
			switch ($property_type)
			{
				case 'attribute' :
					$value = $product->getAttribute($property_name,$get_by_id); break;
				case 'option' :
					$value = $product->getOption($property_name,$get_by_id); break;
			}
			switch ($value_type)
			{
				case 'sum' :
					if (self::$DEBUG || $debug) echo $product->getSku().'.'.$property_type.'.'.$property_name.' = "'.$value.'" x '.$product->getQuantity().'<br />';
					$return_value += $value*$product->getQuantity();
					$value = $return_value;
					break;
				case 'count distinct' :
					if (!isset($distinct_values)) $distinct_values = array();
					if (!in_array($value,$distinct_values)) {
						$distinct_values[] = $value;
						$return_value++;
					}
					break;
				default :
					switch ($compare_symbol)
					{
						case '<'		: $attribute_match = $value<$compare_value;  break;
						case '<='	: $attribute_match = $value<=$compare_value; break;
						case '>'		: $attribute_match = $value>$compare_value;  break;
						case '>='	: $attribute_match = $value>=$compare_value; break;
						case '=='	: $attribute_match = $value==$compare_value; break;
						case '!='	: $attribute_match = $value!=$compare_value; break;
						default		: $attribute_match = false;
					}
					if (self::$DEBUG || $debug) echo $product->getSku().'.'.$property_type.'.'.$property_name.' = "'.$value.'" => '.($attribute_match ? '1' : '0').'<br />';
					if ($value_type=='count' && $attribute_match) $return_value += $product->getQuantity();
					else if ($value_type=='any' && $attribute_match) return 'true';
					else if ($value_type=='all' && !$attribute_match) return 'false';
			}
		}
		switch ($value_type)
		{
			case 'any': return 'false';
			case 'all': return 'true';
			default : return $return_value;
		}
	}
*/
}

interface OS_Product {
	public function getOption($option, $get_by_id);
	public function getAttribute($attribute, $get_by_id);
	public function getName();
	public function getSku();
	public function getQuantity();
}

class OS_Message
{
	public $type;
	public $message;
	public $args;

	public function OS_Message()
	{
		$args = func_get_args();
		if (count($args)==1 && is_array($args[0])) $args = $args[0];
		$this->type = array_shift($args);
		$this->message = array_shift($args);
		$this->args = $args;
	}
}

class OS_Result
{
	public $success;
	public $result;

	public function OS_Result($success, $result=null)
	{
		$this->success = $success;
		$this->result = $result;
	}
}


?>