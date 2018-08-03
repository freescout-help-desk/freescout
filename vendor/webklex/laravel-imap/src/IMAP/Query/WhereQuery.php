<?php
/*
* File:     Query.php
* Category: -
* Author:   M. Goldenbaum
* Created:  21.07.18 18:54
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP\Query;

use Webklex\IMAP\Exceptions\InvalidWhereQueryCriteriaException;
use Webklex\IMAP\Exceptions\MethodNotFoundException;

/**
 * Class Query
 *
 * @package Webklex\IMAP\Query
 * 
 * @method WhereQuery all()
 * @method WhereQuery answered()
 * @method WhereQuery deleted()
 * @method WhereQuery new()
 * @method WhereQuery old()
 * @method WhereQuery recent()
 * @method WhereQuery seen()
 * @method WhereQuery unanswered()
 * @method WhereQuery undeleted()
 * @method WhereQuery unflagged()
 * @method WhereQuery unseen()
 * @method WhereQuery unkeyword($value)
 * @method WhereQuery to($value)
 * @method WhereQuery text($value)
 * @method WhereQuery subject($value)
 * @method WhereQuery since($date)
 * @method WhereQuery on($date)
 * @method WhereQuery keyword($value)
 * @method WhereQuery from($value)
 * @method WhereQuery flagged()
 * @method WhereQuery cc($value)
 * @method WhereQuery body($value)
 * @method WhereQuery before($date)
 * @method WhereQuery bcc($value)
 */
class WhereQuery extends Query {

    /**
     * @var array $available_criteria
     */
    protected $available_criteria = [
        'OR', 'AND',
        'ALL', 'ANSWERED', 'BCC', 'BEFORE', 'BODY', 'CC', 'DELETED', 'FLAGGED', 'FROM', 'KEYWORD',
        'NEW', 'OLD', 'ON', 'RECENT', 'SEEN', 'SINCE', 'SUBJECT', 'TEXT', 'TO',
        'UNANSWERED', 'UNDELETED', 'UNFLAGGED', 'UNKEYWORD', 'UNSEEN'
    ];

    /**
     * Magic method in order to allow alias usage of all "where" methods
     * @param string $name
     * @param array|null $arguments
     *
     * @return mixed
     * @throws MethodNotFoundException
     */
    public function __call($name, $arguments) {
        $method = 'where'.ucfirst($name);
        if(method_exists($this, $method) === true){
            return call_user_func_array([$this, $method], $arguments);
        }

        throw new MethodNotFoundException();
    }

    /**
     * Validate a given criteria
     * @param $criteria
     *
     * @return string
     * @throws InvalidWhereQueryCriteriaException
     */
    protected function validate_criteria($criteria) {
        $criteria = strtoupper($criteria);

        if(in_array($criteria, $this->available_criteria) === false) {
            throw new InvalidWhereQueryCriteriaException();
        }

        return $criteria;
    }

    /**
     * @param string|array $criteria
     * @param mixed $value
     *
     * @return $this
     */
    public function where($criteria, $value = null) {
        if(is_array($criteria)){
            foreach($criteria as $arguments){
                if(count($arguments) == 1){
                    $this->where($arguments[0]);
                }elseif(count($arguments) == 2){
                    $this->where($arguments[0], $arguments[1]);
                }
            }
        }else{
            $criteria = $this->validate_criteria($criteria);
            $value = $this->parse_value($value);

            if($value === null || $value === ''){
                $this->query->push([$criteria]);
            }else{
                $this->query->push([$criteria, $value]);
            }
        }

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return $this
     */
    public function orWhere(\Closure $closure = null) {
        $this->query->push(['OR']);
        if($closure !== null) $closure($this);

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return $this
     */
    public function andWhere(\Closure $closure = null) {
        $this->query->push(['AND']);
        if($closure !== null) $closure($this);

        return $this;
    }

    /**
     * @return WhereQuery
     */
    public function whereAll() {
        return $this->where('ALL');
    }

    /**
     * @return WhereQuery
     */
    public function whereAnswered() {
        return $this->where('ANSWERED');
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereBcc($value) {
        return $this->where('BCC', $value);
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws \Webklex\IMAP\Exceptions\MessageSearchValidationException
     */
    public function whereBefore($value) {
        $date = $this->parse_date($value);
        return $this->where('BEFORE', $date);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereBody($value) {
        return $this->where('BODY', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereCc($value) {
        return $this->where('CC', $value);
    }

    /**
     * @return WhereQuery
     */
    public function whereDeleted() {
        return $this->where('DELETED');
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereFlagged($value) {
        return $this->where('FLAGGED', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereFrom($value) {
        return $this->where('FROM', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereKeyword($value) {
        return $this->where('KEYWORD', $value);
    }

    /**
     * @return WhereQuery
     */
    public function whereNew() {
        return $this->where('NEW');
    }

    /**
     * @return WhereQuery
     */
    public function whereOld() {
        return $this->where('OLD');
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws \Webklex\IMAP\Exceptions\MessageSearchValidationException
     */
    public function whereOn($value) {
        $date = $this->parse_date($value);
        return $this->where('ON', $date);
    }

    /**
     * @return WhereQuery
     */
    public function whereRecent() {
        return $this->where('RECENT');
    }

    /**
     * @return WhereQuery
     */
    public function whereSeen() {
        return $this->where('SEEN');
    }

    /**
     * @param mixed $value
     *
     * @return WhereQuery
     * @throws \Webklex\IMAP\Exceptions\MessageSearchValidationException
     */
    public function whereSince($value) {
        $date = $this->parse_date($value);
        return $this->where('SINCE', $date);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereSubject($value) {
        return $this->where('SUBJECT', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereText($value) {
        return $this->where('TEXT', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereTo($value) {
        return $this->where('TO', $value);
    }

    /**
     * @param string $value
     *
     * @return WhereQuery
     */
    public function whereUnkeyword($value) {
        return $this->where('UNKEYWORD', $value);
    }

    /**
     * @return WhereQuery
     */
    public function whereUnanswered() {
        return $this->where('UNANSWERED');
    }

    /**
     * @return WhereQuery
     */
    public function whereUndeleted() {
        return $this->where('UNDELETED');
    }

    /**
     * @return WhereQuery
     */
    public function whereUnflagged() {
        return $this->where('UNFLAGGED');
    }

    /**
     * @return WhereQuery
     */
    public function whereUnseen() {
        return $this->where('UNSEEN');
    }
}