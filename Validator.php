<?php

namespace Lib;

interface iValidator
{
    public function validate($validator, $name, &$value);
}

class Validator
{
    // validate either greedy or frugal (validate all or just validate till error)
    const TYPE_GREEDY = 1;
    const TYPE_FRUGAL = 2;

	public $rules = [
		// ints
		'int'			=> 'validate_int',
		'opt_int'		=> 'optional_int',
		'int[]'			=> 'validate_int_array',
		'opt_int[]'		=> 'optional_int_array',
		
		// boolean
		'bool'          => 'validate_bool',
		
		// floats
		'float'			=> 'validate_float',
		'float[]'		=> 'validate_float_array',
		
		'array'			=> 'validate_array',
		
		// strings
		'string'		=> 'validate_string',
		'opt_string'	=> 'optional_string',
		
		// misc
		'exists'		=> 'validate_exists',
		'opt_exists'	=> 'optional_exists',
		
		// special rules
		'email'         => 'validate_email',
	];
	
	public $validation_type;
	public $errors = [];
	protected $validation_fields = [];
		
	public static function createGreedy()
	{
	    return new Validator([
	        'type'  => self::TYPE_GREEDY
	    ]);
	}
	
	public function __construct($options = [])
	{
	    $options += [
	        'type'  => self::TYPE_FRUGAL,
	    ];
	    
	    $this->validation_type = $options['type'];
	}
	
	public function clear()
	{
	    $this->validation_fields = [];
	}
	
	public function add_rule($field, $rule)
	{
	    if (!isset($this->validation_fields[$field]))
            $this->validation_fields[$field] = [];

        if (is_array($rule))
        {
            $this->validation_fields[$field] += $rule;
        }
        else if ($rule instanceof \Closure || $rule instanceof iValidator)
        {
            $this->validation_fields[$field][] = $rule;
        }
        else if (is_string($rule))
        {
            $this->validation_fields[$field] += explode('|', $rule);
        }
	}
	
	public function add_error($field, $error)
	{
	    if (!array_key_exists($field, $this->errors))
	    {
	        $this->errors[$field] = [];
	    }
	    
	    $this->errors[$field][] = $error;
	}
	
	public function addError($field, $error)
	{
	    $this->add_error($field, $error);
	}
	
	public function add_rules($fields)
	{
		foreach ($fields as $field => $rule_str)
		{
			if (!isset($this->validation_fields[$field]))
				$this->validation_fields[$field] = [];
				
			$this->validation_fields[$field] += explode('|', $rule_str);
		}
	}
	
	public function isValid()
	{
	    return !(bool) count($this->errors);
	}

	public function validate(&$data, $fields = [])
	{
	    // see if we are already invalid
	    if ($this->validation_type == self::TYPE_FRUGAL && !$this->isValid())
	        return false;
	    
        // append the rules
	    foreach ($fields as $field => $rule)
	        $this->add_rule($field, $rule);

		$erp = error_reporting();
		$erp = error_reporting($erp & ~E_NOTICE);

        $all_passed = ($this->errors == false);

		foreach ($this->validation_fields as $field => $rules)
		{	
			if (!is_array($this->errors[$field]))
                $this->errors[$field] = [];

            $cur_passed = true;

			foreach ($rules as $rule)
			{  
				// special case for custom
				if ($rule instanceof \Closure)
				{
				    $all_passed &= $cur_passed &= $rule($this, $data, $field);
				}
				else if ($rule instanceof iValidator)
				{
				    $all_passed &= $cur_passed &= $rule->validate($this, $field, $data[$field]);
				}
				else if (!is_string($rule))
				{
				    break;  // nothing else to do if not a string
				}
				else if (isset($this->rules[$rule]))
				{
					$all_passed &= $cur_passed &= (bool) $this->{$this->rules[$rule]}($data, $field, $this->errors);
				}
				
				if ($cur_passed == false)
				{
					break;
				}
			}
			
			// if there weren't any errors, then unset the field
			if (!count($this->errors[$field]))
			    unset($this->errors[$field]);
		
			if ($this->validation_type == self::TYPE_FRUGAL && $all_passed == false)
				break;
		}

		error_reporting($erp);
		
		return $all_passed;
	}
	
	public function validate_exists(&$data, $field)
	{
		$valid = array_key_exists($field, $data);
		
		if (!$valid)
		{
		    $this->addError(
		        $field,
		        sprintf('%s does not exist', $field)
		    );
		    return false;
		}
		
		return true;
	}
	
	public function optional_exists(&$data, $field)
	{
		if (!isset($data[$field]))
			$data[$field] = NULL;
		
		return true;	// this never fails
	}
	
	public function validate_int(&$data, $field)
	{
		$passed = is_numeric($data[$field]);
		$data[$field] = intval($data[$field]);
		
		if (!$passed)
		    $this->add_error($field, "not a valid integer");
		
		return $passed;
	}
	
	public function optional_int(&$data, $field, $errors)
	{
		if (!isset($data[$field]))
		{
			$data[$field] = NULL;
			return true;
		}
		
		return $this->validate_int($data, $field, $errors);
	}
	
	public function validate_int_array(&$data, $field, &$errors)
	{
		$passed = is_array($data[$field]) && count($data[$field]);
		
		if ($passed)
		{			
            // validate the individual items now
            foreach ($data[$field] as $item)
            {
                if (!is_numeric($item))
                {
                    $passed = false;
                    break;
                }
            }
        }

        if ($passed)
        {
            // convert to ints
            $data[$field] = array_map('intval', $data[$field]);
        }
        else
        {
            $errors[$field][] = "not a valid integer array";
        }

		return $passed;
	}
	
	public function optional_int_array(&$data, $field)
	{
	    if (!isset($data[$field]))
	    {
	        $data[$field] = [];
	        return true;
	    }
	    
	    // else we validate as an integer array
	    return $this->validate_int_array($data, $field, $errors);
	}
	
	public function validate_bool(&$data, $field)
	{
	    if (!$this->validate_exists($data, $field)) {
	        return false;
	    }
	    
	    $data[$field] = boolval($data[$field]);
	    
	    return true;
	}
	
	public function validate_float(&$data, $field)
	{
		$passed = is_numeric($data[$field]);
		$data[$field] = floatval($data[$field]);
		return $passed;
	}
	
	public function validate_array(&$data, $field)
	{
		$passed = is_array($data[$field]) && count($data[$field]);
		return $passed;
	}
	
	public function validate_string(&$data, $field, &$errors)
	{
		$passed = is_string($data[$field]) && strlen($data[$field]);
		
	    if (!$passed)
	        $errors[$field][] = "not a valid string";
		
		return $passed;
	}
		
	public function optional_string(&$data, $field)
	{
		if (!isset($data[$field]) || !is_string($data[$field]))
		{
			$data[$field] = '';
		}
		
		return true;
	}
	
	public function validate_email(&$data, $field)
	{
	    if (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $data[$field]))
	    {
	        $this->add_error($field, 'Is not a valid email');
	    }
	    
	    return true;
	}
	
	public function regex($reg, $reg_type)
	{
	    return function($v, &$data, $field) use ($reg, $reg_type) {
	        if (preg_match($reg, $data[$field])) {
	            return true;
	        }
	        
	        $v->addError(
                $field,
                sprintf("'%s' is not a valid %s", $data[$field], $reg_type)
            );
            return false;
	    };
	}
}
