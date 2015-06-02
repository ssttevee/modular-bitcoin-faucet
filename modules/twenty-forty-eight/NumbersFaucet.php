<?php

use AllTheSatoshi\Faucet\BaseFaucet;

class NumbersFaucet extends BaseFaucet {

    const EMPTY_TILE = null;

    private $size = 4;

    function __construct($btcAddress) {
        parent::__construct("2048", $btcAddress);
    }

    function ajax($action, $post) {
        // TODO: Implement ajax() method.
    }

    function satoshi() {
        // TODO: Implement satoshi() method.
    }

    /**
     * Either start a new game or continue an unfinished game
     */
    function start() {
        $grid = $this->grid;
        if(empty($grid)) {
            $grid = $this->newGame();
        }

        return $this->gridToJs($grid);
    }

    /**
     * Create a new game
     *
     * @return null[][]
     */
    private function newGame() {
        $grid = [];
        for($x = 0; $x < $this->size; $x++) {
            $grid[$x] = [];
            for($y = 0; $y < $this->size; $y++) {
                $grid[$x][$y] = self::EMPTY_TILE;
            }
        }
        $this->grid = $grid;
        $this->addRandomTile();
        $this->addRandomTile();
        return $this->grid;
    }

    private function gridToJs($grid = null) {
        if(empty($grid)) $grid = $this->grid;


        $str = "[";
        foreach($grid as $x) {
            $str .= "[";
            foreach($x as $y) {
                $str .= (isset($y) ? $y : 0) . ", ";
            }
            $str .= "],";
        }
        $str .= "]";

        return $str;
    }

    /**
     * Push all tiles in the chosen direction
     *
     * @param int $direction
     * @return mixed
     */
    function move($direction) {
        // 0: up, 1: right, 2: down, 3: left
        if(!is_int($direction) || $direction < 0 || $direction > 4) return "Bad direction";

        $moved = false;
        $vector = $this->getVector($direction);
        $traversals = $this->buildTraversals($vector);

        foreach($traversals["x"] as $x) {
            foreach($traversals["y"] as $y) {
                $cell = ["x" => $x, "y" => $y];
                $tile = $this->getCellContent($cell);

                if(isset($tile)) {
                    $positions = $this->findFarthestPosition($cell, $vector);
                    $next      = $this->getCellContent($positions["next"]);

                    // Only one merger per row traversal?
                    if(isset($next) && $next["value"] == $tile["value"] && empty($next["mergedFrom"])) {
                        $merged = ["x" => $next["x"], "y" => $next["y"], "value" => $next["value"]*2];
                        $merged["mergedFrom"] = ["tile" => $tile, "next" => $next];

                        $this->setTile($merged);
                        $this->removeTile($tile);

                        // Converge the two tiles' positions
                        $tile["x"] = $next["x"];
                        $tile["y"] = $next["y"];

                        // Update the score
                        $this->score += $merged["value"];
                    } else {
                        $this->moveTile($tile, $positions["farthest"]);
                    }

                    if($cell["x"] != $tile["x"] || $cell["y"] != $tile["y"]) {
                        // The tile moved from its original cell!
                        $moved = true;
                    }
                }
            }
        }

        if($moved) {
            $this->addRandomTile();

            if($this->isGameOver()) {
                $this->game_over = true;
                return ["game_over" => true];
            }

        }
        return ["moved" => $moved, "grid" => $this->gridToJs()];

    }

    /**
     * Get the vector representing the chosen direction
     *
     * @param integer $direction Up, right, down, or left
     * @return array Vector representation of the direction
     */
    private function getVector($direction) {
        // Vectors representing tile movement
        $map = [
            "0" => ["x" =>  0, "y" => -1], // Up
            "1" => ["x" =>  1, "y" =>  0], // Right
            "2" => ["x" =>  0, "y" =>  1], // Down
            "3" => ["x" => -1, "y" =>  0], // Left
        ];

        return $map[$direction];
    }

    /**
     * Build a list of positions to traverse in the right order
     *
     * @param array $vector @see NumbersFaucet::getVector
     * @return array
     */
    private function buildTraversals($vector) {
        $x = [];
        $y = [];

        for($pos = 0; $pos < $this->size; $pos++) {
            $x[] = $pos;
            $y[] = $pos;
        }

        if($vector["x"] == 1) $x = array_reverse($x);
        if($vector["y"] == 1) $y = array_reverse($y);

        return ["x" => $x, "y" => $y];
    }

    /**
     * Find the farthest position that the tile can go
     *
     * @param array $cell
     * @param array $vector @see NumbersFaucet::getVector
     * @return array
     */
    private function findFarthestPosition($cell, $vector) {
        // Progress towards the vector direction until an obstacle is found
        do {
            $previous = $cell;
            $cell = ["x" => $previous["x"] + $vector["x"], "y" => $previous["y"] + $vector["y"]];
        } while (
            $cell["x"] >= 0 && $cell["x"] < $this->size && $cell["y"] >= 0 && $cell["y"] < $this->size && // check if is in bounds
            $this->getCellContent($cell) == null // check if next cell is occupied
        );

        return [
            "farthest" => $previous,
            "next" => $cell // Used to check if a merge is required
        ];
    }

    /**
     * Get the contents of the specified cell
     *
     * @param array $cell Cell in question
     * @return array|null tile if is filled, otherwise null
     */
    private function getCellContent($cell) {
        if($cell["x"] < 0 || $cell["x"] >= $this->size || $cell["y"] < 0 || $cell["y"] >= $this->size) return null;

        $r = $this->col->findOne(
            [
                "address" => $this->address
            ],
            [
                "{$this->name}.grid" => [
                    "\$slice" => [$cell["x"], 1]
                ]
            ]
        );

        $value = $r[$this->name]["grid"][0][$cell["y"]];

        if(empty($value) || $value == self::EMPTY_TILE) return null;

        $cell["value"] = $value;
        return $cell;
    }

    private function addRandomTile($grid = null) {
        $cells = $this->getEmptyCells($grid);
        if (count($cells) > 0) {
            $value = mt_rand() / mt_getrandmax() < 0.9 ? 2 : 4;
            $tile = $cells[mt_rand(0, count($cells) - 1)];
            $tile["value"] = $value;

            $this->setTile($tile);
        }
    }

    private function removeTile($tile) {
        $tile["value"] = self::EMPTY_TILE;
        $this->setTile($tile);
    }

    /**
     * Set tile value at given x and y
     *
     * @param $tile
     */
    private function setTile($tile) {
        $this->col->update(
            [
                "address" => $this->address
            ],
            [
                "\$set" => [
                    "{$this->name}.grid.{$tile["x"]}.{$tile["y"]}" => $tile["value"]
                ]
            ]
        );
    }

    /**
     * Move tile from cell A to cell B
     *
     * @param $fromTile
     * @param $toCell
     */
    private function moveTile(&$fromTile, $toCell) {
        $this->col->update(
            [
                "address" => $this->address
            ],
            [
                "\$set" => [
                    "{$this->name}.grid.{$fromTile["x"]}.{$fromTile["y"]}" => self::EMPTY_TILE,
                    "{$this->name}.grid.{$toCell["x"]}.{$toCell["y"]}" => $fromTile["value"]
                ]
            ]
        );
        $fromTile["x"] = $toCell["x"];
        $fromTile["y"] = $toCell["y"];
    }

    /**
     * Get all empty cells
     *
     * @return array
     */
    private function getEmptyCells($grid = null) {
        if(empty($grid)) $grid = $this->grid;
        $empty_cells = [];
        for($x = 0; $x < $this->size; $x++) {
            for($y = 0; $y < $this->size; $y++) {
                if($grid[$x][$y] == self::EMPTY_TILE) $empty_cells[] = ["x" => $x, "y" => $y];
            }
        }
        return $empty_cells;
    }

    /**
     * Check whether or not the game is over
     *
     * @return bool
     */
    private function isGameOver() {
        return (
            $this->game_over || // Game already ended
            count($this->getEmptyCells()) > 0 || // if there are empty cells, game's not over yet
            $this->hasTileMatches() // matches can be made
        );
    }

    /**
     * Check whether or not any matches can be made (expensive)
     *
     * @return bool
     */
    private function hasTileMatches() {
        for($x = 0; $x < $this->size; $x++) {
            for($y = 0; $y < $this->size; $y++) {
                $tile = $this->getCellContent(["x" => $x, "y" => $y]);

                if(isset($tile)) {
                    for($direction = 0; $direction < 4; $direction++) {
                        $vector = $this->getVector($direction);
                        $cell = ["x" => $x + $vector["x"], "y" => $y + $vector["y"]];

                        if($tile["value"] == $this->getCellContent($cell)["value"]) {
                            // These two tiles can be merged
                            return true;
                        }
                    }
                }
            }
        }

      return false;
    }

}