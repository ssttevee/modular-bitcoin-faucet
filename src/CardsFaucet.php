<?php

namespace AllTheSatoshi\Faucet;

use AllTheSatoshi\FaucetManager;
use AllTheSatoshi\Util\Config as _c;

class CardsFaucet extends BaseFaucet {

    private static function getNewDeck() {
        return [
            'DA','D2','D3','D4','D5','D6','D7','D8','D9','DT','DJ','DQ','DK',
            'CA','C2','C3','C4','C5','C6','C7','C8','C9','CT','CJ','CQ','CK',
            'HA','H2','H3','H4','H5','H6','H7','H8','H9','HT','HJ','HQ','HK',
            'SA','S2','S3','S4','S5','S6','S7','S8','S9','ST','SJ','SQ','SK',
            'joker'
        ];
    }

    private static function cardValue($card) {
        switch($card{1}) {
            case "A": return 1; break;
            case "2": return 2; break;
            case "3": return 3; break;
            case "4": return 4; break;
            case "5": return 5; break;
            case "6": return 6; break;
            case "7": return 7; break;
            case "8": return 8; break;
            case "9": return 9; break;
            case "T": return 10; break;
            case "J": return 11; break;
            case "Q": return 12; break;
            case "K": return 13; break;
            case "o": return 26; break;
            default: return 0;
        }
    }

    private static function compareCards($a, $b) {
        return self::cardValue($a) > self::cardValue($b) ? +1 : -1;
    }

    function __construct($btcAddress) {
        parent::__construct('lucky_joker', $btcAddress);
    }

    function ajax($action, $post) {
        if($action == "reveal") {
            try {
                return ["success" => true, "message" => "You revealed 1 card.", "card" => $this->reveal(), "combo_multiplier" => round($this->getComboMultiplier(), 1), "cards_left" => $this->countDeck(), "burned_count" => $this->countBurned(), "revealed_cards" => $this->getRevealed()];
            } catch(\Exception $e) {
                return $e->getMessage();
            }
        } else if($action == "burn") {
            try {
                $this->burn();
                return ["success" => true, "message" => "You burned 1 card.", "combo_multiplier" => round($this->getComboMultiplier(), 1), "cards_left" => $this->countDeck(), "burned_count" => $this->countBurned(), "revealed_cards" => $this->getRevealed()];
            } catch(\Exception $e) {
                return $e->getMessage();
            }
        } else if($action == "new-game") {
            if(!$this->isInGame()) {
                $shuffle_times = array_key_exists("shuffle_times", $post) ? intval($post["shuffle_times"]) : 5;
                if($shuffle_times < 1) return "You must shuffle at least once.";
                else if($shuffle_times > 50) return "You cannot shuffle more than 50 times.";
                else $this->newGame($shuffle_times);
                return ["success" => "true", "message" => "You have successfully started a new game."];
            } else {
                return "You're already playing.";
            }
        } else if($action == "claim") {
            if(!$post["is_human"]) return "not_human";
            return $this->claim();
        }
    }

    function isReady() {
        return $this->getWaitTime() <= 0;
    }

    function getWaitTime() {
        if($this->last_game == null) return 0;
        return $this->last_game->sec - (time() - _c::ini("general","dispenseInterval"));
    }

    function isInGame() {
        return $this->hash != null;
    }

    function newGame($shuffle_times) {
        $deck = self::getNewDeck();
        for($i = 0; $i < $shuffle_times; $i++) $this->shuffle($deck);

        $this->deck = $deck;
        $this->revealed = [];
        $this->burnt = [];
        $this->secret = implode("-", $deck) . "-" . $this->generateNonce();
        $this->hash = hash('sha256', $this->secret);
    }

    function shuffle(&$deck) {
        $new = [];
        while(count($deck) > 0) {
            $i = mt_rand(0, count($deck) - 1);
            array_push($new, $deck[$i]);
            array_splice($deck, $i, 1);
        }
        $deck = $new;
    }

    function countDeck() {
        return count($this->deck);
    }

    function countBurned() {
        return count($this->burnt);
    }

    function getRevealed() {
        $revealed = $this->revealed;
        return $revealed == null ? [] : $revealed;
    }

    function draw() {
        if(count($this->deck) < 1) throw new \Exception("There are no more card in the deck.");
        $card = _c::getCollection('users')->findOne(['address' => $this->address], [$this->name . '.deck' => ['$slice' => 1]])[$this->name]["deck"][0];
        _c::getCollection('users')->update(['address' => $this->address], ['$pop' => [$this->name . '.deck' => -1]]);
        return $card;
    }

    function reveal() {
        if(count($this->revealed) >= 5) throw new \Exception("Cannot reveal more than 4 cards.");
        $card = $this->draw();
        _c::getCollection('users')->update(['address' => $this->address], ['$push' => [$this->name . '.revealed' => $card]]);
        return $card;
    }

    function burn() {
        $card = $this->draw();
        _c::getCollection('users')->update(['address' => $this->address], ['$push' => [$this->name . '.burnt' => $card]]);
    }

    function getComboMultiplier($revealed = null) {
        if($revealed == null) $revealed = $this->revealed;
        if(empty($revealed)) return 1;

        usort($revealed, ["\\AllTheSatoshi\\Faucet\\CardsFaucet", "compareCards"]);

        $cs = [];
        $cv = [];
        for($i = 0; $i < count($revealed); $i++) {
            $cs[$i] = $revealed[$i]{0};
            $cv[$i] = $this->cardValue($revealed[$i]);
        }
        
        $multiplier = 1;

        if(count($cv)>4 && $cv[0]==1 && $cv[1]==10 && $cv[2]==11 && $cv[3]==12 && $cv[4]==13 && $cs[0]==$cs[1] && $cs[1]==$cs[2] && $cs[2]==$cs[3] && $cs[3]==$cs[4]) $multiplier = 10; // royal flush
        else if(count($cv)>4 && ($cv[0]+1)==$cv[1] && ($cv[1]+1)==$cv[2] && ($cv[2]+1)==$cv[3] && ($cv[3]+1)==$cv[4] && $cs[0]==$cs[1] && $cs[1]==$cs[2] && $cs[2]==$cs[3] && $cs[3]==$cs[4]) $multiplier = 9; // straight flush
        else if((count($cv)>3 && $cv[0]==$cv[1] && $cv[1]==$cv[2] && $cv[2]==$cv[3]) || (count($cv)>4 && $cv[1]==$cv[2] && $cv[2]==$cv[3] && $cv[3]==$cv[4])) $multiplier = 8; // four of a kind
        else if(count($cv)>4 && (($cv[0]==$cv[1] && $cv[1]==$cv[2] && $cv[3]==$cv[4]) || ($cv[0]==$cv[1] && $cv[2]==$cv[3] && $cv[3]==$cv[4]))) $multiplier = 7; // full house
        else if(count($cv)>4 && $cs[0]==$cs[1] && $cs[1]==$cs[2] && $cs[2]==$cs[3] && $cs[3]==$cs[4]) $multiplier = 6; // flush
        else if(count($cv)>4 && $cv[0]==1 && $cv[1]==10 && $cv[2]==11 && $cv[3]==12 && $cv[4]==13) $multiplier = 5; // highest straight (10-J-Q-K-A)
        else if(count($cv)>4 && ($cv[0]+1)==$cv[1] && ($cv[1]+1)==$cv[2] && ($cv[2]+1)==$cv[3] && ($cv[3]+1)==$cv[4]) $multiplier = 5; // straight
        else if(count($cv)>2 && ($cv[0]==$cv[1] && $cv[1]==$cv[2]) || (count($cv)>3 && $cv[1]==$cv[2] && $cv[2]==$cv[3]) || (count($cv)>4 && $cv[2]==$cv[3] && $cv[3]==$cv[4])) $multiplier = 4; // three of a kind
        else if((count($cv)>3 && $cv[0]==$cv[1] && $cv[2]==$cv[3]) || (count($cv)>4 && $cv[0]==$cv[1] && $cv[3]==$cv[4]) || (count($cv)>4 && $cv[1]==$cv[2] && $cv[3]==$cv[4])) $multiplier = 3; // two pairs
        else if((count($cv)>1 && $cv[0]==$cv[1]) || (count($cv)>2 && $cv[1]==$cv[2]) || (count($cv)>3 && $cv[2]==$cv[3]) || (count($cv)>4 && $cv[3]==$cv[4])) $multiplier = 2; // one pair
        
        return pow($multiplier, 1.8);
    }

    function satoshi() {
        $amount = 0;
        $revealed = $this->revealed;
        foreach($revealed as $card) {
            $value = substr($card, 1);
            $amount += ($value == "A" ? 14 : ($value == "T" ? 10 : ($value == "J" ? 11 : ($value == "Q" ? 12 : ($value == "K" ? 13 : ($value == "oker" ? 26 : intval($value) ) ) ) ) ) );
        }
        return $amount * (6 - count($revealed)) * $this->getComboMultiplier($revealed);
    }

    function claim() {
        $revealed = $this->revealed;
        if(count($revealed) <= 0) {
            return "Nothing to collect";
        } else {
            $amount = $this->satoshi();

            // log this claim
            _c::getCollection($this->name . ".history")->insert(["address" => $this->address, "hand" => $revealed, "time" => new \MongoDate()]);

            // clear the table and set the time
            _c::getCollection("users")->update(["address" => $this->address], ['$set' => [$this->name => ['last_game' => new \MongoDate(), "claims" => $this->claims + 1]]]);

            FaucetManager::_($this->address)->addBalance($amount);
            return ["success" => true, "amount" => $amount, "message" => "Successfully added " . $amount . " satoshi to your balance!"];
        }
    }

}