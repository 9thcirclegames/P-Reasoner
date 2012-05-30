<?php

// ----------------------------------------------------------------------------------
// Class: Statement
// ----------------------------------------------------------------------------------

/**
 * An RDF statement.
 * In this implementation, a statement is not itself a resource. 
 * If you want to use a a statement as subject or object of other statements,
 * you have to reify it first.
 *  
 * @author Chris Bizer <chris@bizer.de>
 * @version  $Id: Statement.php 268 2006-05-15 05:28:09Z tgauss $
 * @package model
 */
class Statement extends Object
{
	/**
	* Subject of the statement
	*
	* @var		object resource	
	* @access	private
	*/	
	public $subj = NULL;

	/**
	* Predicate of the statement
	*
	* @var		object resource	
	* @access	private
	*/		
    public $pred = NULL;
	
  	/**
	* Object of the statement
	*
	* @var		object node	
	* @access	private
	*/		
    public $obj = NULL;

  /**
   * The parameters to constructor are instances of classes and not just strings
   *
   * @param	object	node $subj
   * @param	object	node $pred
   * @param	object	node $obj
	* @throws	PhpError
   */
   public function __construct(Node $subj, Node $pred, Node $obj)
   {
		if (is_a($subj, 'Resource') !== TRUE) {
			$errmsg = RDFAPI_ERROR . 
					  '(class: Statement; method: new): Resource expected as subject. Found: ' . get_class($subj);
			trigger_error($errmsg, E_USER_ERROR); 
		}
		if ((is_a($pred, 'Resource') && !is_a($pred, 'BlankNode')) !== TRUE) {
			$errmsg = RDFAPI_ERROR . 
					  '(class: Statement; method: new): Resource expected as predicate, no blank node allowed.';
			trigger_error($errmsg, E_USER_ERROR); 
		}
		if ((is_a($obj, 'Resource') || is_a($obj, 'Literal')) !== TRUE) {
			$errmsg = RDFAPI_ERROR . 
					  '(class: Statement; method: new): Resource or Literal expected as object.';
			trigger_error($errmsg, E_USER_ERROR); 
		}
		
		$this->pred  = $pred;
		$this->subj  = $subj;
		$this->obj   = $obj;
	}

  /**
   * Returns the subject of the triple.
   * @access	public 
   * @return	object node
   */
	public function getSubject()
	{
		return $this->subj;
	}

  /**
   * Returns the predicate of the triple.
   * @access	public 
   * @return	object node
   */
	public function getPredicate()
	{
		return $this->pred;
	}

  /**
   * Returns the object of the triple.
   * @access	public 
   * @return	object node
   */
	public function getObject()
	{
		return $this->obj;
	}
	
    /**
   * Retruns the hash code of the triple.	
   * @access	public 
   * @return string
   */
	public function hashCode()
	{
		return md5($this->subj->getLabel() . $this->pred->getLabel()  . $this->obj->getLabel());
	}

  /**
   * Dumps the triple.	
   * @access	public 
   * @return string
   */  
	public function toString()
	{ 
		return  'Triple(' . $this->subj->toString() . ', ' . $this->pred->toString() . ', ' . $this->obj->toString() . ')';
	}

  /**
   * Returns a toString() serialization of the statements's subject.
   *
   * @access	public 
   * @return	string 
   */  
	public function toStringSubject()
	{
		return $this->subj->toString();
	}

  /**
   * Returns a toString() serialization of the statements's predicate.
   *
   * @access	public 
   * @return	string 
   */  
	public function toStringPredicate()
	{
		return $this->pred->toString();
	}

  /**
   * Reurns a toString() serialization of the statements's object.
   *
   * @access	public 
   * @return	string 
   */  
	public function toStringObject()
	{
		return $this->obj->toString();
	}

  /**
   * Returns the URI or bNode identifier of the statements's subject.
   *
   * @access	public 
   * @return	string 
   */  
	public function getLabelSubject()
	{
		return $this->subj->getLabel();
	}

  /**
   * Returns the URI of the statements's predicate.
   *
   * @access	public 
   * @return	string 
   */  
	public function getLabelPredicate()
	{
		return $this->pred->getLabel();
	}

  /**
   * Reurns the URI, text or bNode identifier of the statements's object.
   *
   * @access	public 
   * @return	string 
   */  
	public function getLabelObject()
	{
		return $this->obj->getLabel();
	}
  
  /**
   * Checks if two statements are equal.
   * Two statements are considered to be equal if they have the
   * same subject, predicate and object. A statement can only be equal 
   * to another statement object. 
   * @access	public 
   * @param		object	statement $that
   * @return	boolean 
   */  

	public function equals ($that)
	{
		if ($this === $that) {
			return TRUE;
		}
		if ($that === NULL || is_a($that, 'Statement') !== TRUE) {
			return FALSE;
		}
		
		## is this really needed?
		return
			$this->subj->equals($that->getSubject()) &&
			$this->pred->equals($that->getPredicate()) &&
			$this->obj->equals($that->getObject());
	}

  /**
   * Compares two statements and returns integer less than, equal to, or greater than zero.
   * Can be used for writing sorting function for models or with the PHP function usort(). 
   *
   * @access	public 
   * @param		object	statement &$that
   * @return	boolean 
   */  
	public function compare(&$that)
	{
		return statementsorter($this, $that);
		// statementsorter function see below
	} 
      
	  
  /**
   * Reifies a statement.
   * Returns a new MemModel that is the reification of the statement.
   * For naming the statement's bNode a Model or bNodeID must be passed to the method.   
   *
   * @access	public 
   * @param		mixed	&$model_or_bNodeID
   * @return	object	model
   */
  public static function reify(&$model_or_bNodeID)
  {
		if (is_a($model_or_bNodeID, 'MemModel')) {
			// parameter is model
			$statementModel = new MemModel($model_or_bNodeID->getBaseURI());
			$thisStatement = new BlankNode($model_or_bNodeID);
		} else {
			// parameter is bNodeID
			$statementModel = new MemModel();
			$thisStatement = &$model_or_bNodeID;
		} 
		
		$RDFstatement = new Resource(RDF_NAMESPACE_URI . RDF_STATEMENT);
		$RDFtype = new Resource(RDF_NAMESPACE_URI . RDF_TYPE);
		$RDFsubject = new Resource(RDF_NAMESPACE_URI . RDF_SUBJECT);
		$RDFpredicate = new Resource(RDF_NAMESPACE_URI . RDF_PREDICATE);
		$RDFobject = new Resource(RDF_NAMESPACE_URI . RDF_OBJECT);
		
		$statementModel->add(new Statement($thisStatement, $RDFtype, $RDFstatement));
		$statementModel->add(new Statement($thisStatement, $RDFsubject, $this->getSubject()));
		$statementModel->add(new Statement($thisStatement, $RDFpredicate, $this->getPredicate()));
		$statementModel->add(new Statement($thisStatement, $RDFobject, $this->Object()));
		
		return $statementModel;
  }
  
} // end: Statement


/**
* Comparison function for comparing two statements.
* statementsorter() is used by the PHP function usort ( array array, callback cmp_function)
*
* @access	private 
* @param		object Statement	$a
* @param		object Statement	$b
* @return	integer less than, equal to, or greater than zero  
* @throws phpErrpr
*/
function statementsorter($a, $b)
{
	  //Compare subjects
	  $x = $a->getSubject();
	  $y = $b->getSubject();
	  $r = strcmp($x->getLabel(),$y->getLabel());
	  if ($r!=0) return $r;
	  //Compare predicates
	  $x=$a->getPredicate();
	  $y=$b->getPredicate();
	  $r=strcmp($x->getURI(),$y->getURI());
	  if ($r!=0) return $r;
	  //Final resort, compare objects
	  $x = $a->getObject();
	  $y = $b->getObject();
	  return strcmp($x->toString(), $y->toString());
}
	
?>