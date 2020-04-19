<?php
/**
 * This class calculates ratings based on the Elo system used in chess.
 *
 * @author Michal Chovanec <michalchovaneceu@gmail.com>
 * @copyright Copyright © 2012 - 2014 Michal Chovanec
 * @license Creative Commons Attribution 4.0 International License
 * 
 * Edited by Luis Cereda to be used with variable K and G factors for football.
 */
 
//namespace Rating;
class Rating
{
    /**
     * @var int The K Factor used.
     */
    const WIN = 1;
    const DRAW = 0.5;
    const LOST = 0;
    /**
     * Protected & private variables.
     */
    protected $_ratingA;
    protected $_ratingB;
    
    protected $_scoreA;
    protected $_scoreB;
    protected $_expectedA;
    protected $_expectedB;
    protected $_newRatingA;
    protected $_newRatingB;
    protected $_kfactor;
    protected $_homefactor;
    protected $_goaldif;

    /**
     * Constructor function which does all the maths and stores the results ready
     * for retrieval.
     *
     * @param int $ratingA Current rating of A
     * @param int $ratingB Current rating of B
     * @param int $scoreA Score of A
     * @param int $scoreB Score of B
     */
    public function  __construct($ratingA,$ratingB,$scoreA,$scoreB,$kfactor,$homefactor,$goaldif)
    {
        $this->setNewSettings($ratingA, $ratingB, $scoreA, $scoreB, $kfactor, $homefactor, $goaldif);
    }
    /**
     * Set new input data.
     *
     * @param int $ratingA Current rating of A
     * @param int $ratingB Current rating of B
     * @param int $scoreA Score of A
     * @param int $scoreB Score of B
     * @param int $kfactor Competition Factor
     * @param int $homefactor Home/Away Factor
     * @param int $goaldif Goal Difference
     * @return self
     */
    public function setNewSettings($ratingA,$ratingB,$scoreA,$scoreB,$kfactor,$homefactor,$goaldif)
    {

        $this -> _ratingA = $ratingA;
        $this -> _ratingB = $ratingB;
        $this -> _scoreA = $scoreA;
        $this -> _scoreB = $scoreB;
        $this -> _kfactor = $kfactor;
        $this -> _homefactor = $homefactor;
        $this -> _goaldif = $goaldif;
        $expectedScores = $this -> _getExpectedScores($this -> _ratingA,$this -> _ratingB,$this -> _homefactor);
        $this -> _expectedA = $expectedScores['a'];
        $this -> _expectedB = $expectedScores['b'];
        $newRatings = $this ->_getNewRatings($this -> _ratingA, $this -> _ratingB, $this -> _expectedA, $this -> _expectedB, $this -> _scoreA, $this -> _scoreB, $this -> _kfactor, $this -> _goaldif);
        $this -> _newRatingA = $newRatings['a'];
        $this -> _newRatingB = $newRatings['b'];
        return $this;
    }
    /**
     * Retrieve the calculated data.
     *
     * @return array An array containing the new ratings for A and B.
     */
    public function getNewRatings()
    {
        return array (
            'a' => $this -> _newRatingA,
            'b' => $this -> _newRatingB
        );
    }
    // Protected & private functions begin here
    /**
     * @param int $ratingA The Rating of Player A
     * @param int $ratingB The Rating of Player B
     * @return array
     */
    protected function _getExpectedScores($ratingA,$ratingB,$homefactor)
    {
        $expectedScoreA = 1 / ( 1 + ( pow( 10 , ( $ratingB - ($ratingA+$homefactor) ) / 400 ) ) );
        $expectedScoreB = 1 / ( 1 + ( pow( 10 , ( ($ratingA+$homefactor) - $ratingB ) / 400 ) ) );


       
        return array (
            'a' => $expectedScoreA,
            'b' => $expectedScoreB
        );
    }
    /**
     * @param int $ratingA The Rating of Player A
     * @param int $ratingB The Rating of Player A
     * @param int $expectedA The expected score of Player A
     * @param int $expectedB The expected score of Player B
     * @param int $scoreA The score of Player A
     * @param int $scoreB The score of Player B
     * @return array
     */
    protected function _getNewRatings($ratingA,$ratingB,$expectedA,$expectedB,$scoreA,$scoreB,$kfactor,$goaldif)
    {
        if($goaldif == 1 or $goaldif == 0) {
            $gfactor = 1;
        } elseif ($goaldif == 2) {
            $gfactor = 1.5; 
        } else {
            $gfactor = (11 + $goaldif) / 8;
        }

        $newRatingA = $ratingA + ( $kfactor * $gfactor * ( $scoreA - $expectedA ) );
        $newRatingB = $ratingB + ( $kfactor * $gfactor * ( $scoreB - $expectedB ) );

        return array (
            'a' => $newRatingA,
            'b' => $newRatingB
        );
    }  
}

?>