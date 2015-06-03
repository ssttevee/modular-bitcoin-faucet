<?php

use AllTheSatoshi\Faucet\BaseFaucet;

class NumbersFaucet extends BaseFaucet {

    const EMPTY_TILE = null;

    private $size = 4;
    private $tiles = [];
    private $game_over = false;
    private $score = false;

    /**
     * Get all the possible cells in the grid
     *
     * @param NumbersFaucet $game instance of 2048
     * @return array all possible cells in the grid (4x4 by default)
     */
    private static function allCells(NumbersFaucet $game) {
        $tiles = [];

        for($x = 0; $x < $game->size; $x++) {
            for($y = 0; $y < $game->size; $y++) {
                $tiles["$x-$y"] = ["x" => $x, "y" => $y];
            }
        }

        return $tiles;
    }

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
     * Saves tiles, score, and game status to the database
     */
    private function save() {
        // Clean tiles
        foreach($this->tiles as &$tile) {
            unset($tile["mergedFrom"]);
            unset($tile["previousPosition"]);
        }

        $this->__set("tiles", $this->tiles);
        $this->__set("score", $this->score);
        $this->__set("game_over", $this->game_over);
    }

    /**
     * Fetches updated tiles, score, and game status
     */
    private function update() {
        $this->tiles = $this->__get("tiles");
        $this->score = $this->__get("score");
        $this->game_over = $this->__get("game_over");
    }

    /**
     * Either start a new game or continue an unfinished game
     *
     * @param integer $size the size of the grid for new games
     * @return array response for the web socket client
     */
    function start($size = 4) {
        $this->update();

        if(empty($this->tiles)) {
            $this->size = $size;
            $this->newGame();
        }

        $this->save();
        return ["grid" => $this->export(), "game_over" => $this->game_over];
    }

    /**
     * Create a new game
     *
     * @return null[][]
     */
    private function newGame() {
        $this->tiles = [];
        $this->addRandomTile();
        $this->addRandomTile();
    }

    /**
     * Translate tiles to 2048 format
     *
     * @return array
     */
    private function export() {
        $grid = [];

        for($x = 0; $x < $this->size; $x++) {
            $grid[$x] = [];
            for($y = 0; $y < $this->size; $y++) {
                $grid[$x][$y] = $this->getCellTile(["x" => $x, "y" => $y]);
            }
        }

        return $grid;
    }

    /**
     * Push all tiles in the chosen direction
     *
     * @param int $direction 0: up, 1: right, 2: down, 3: left
     * @return array response for the web socket client
     */
    function move($direction) {
        if($this->game_over) return ["grid" => $this->export(), "game_over" => $this->game_over];
        if(!is_int($direction) || $direction < 0 || $direction > 4) return "Bad direction";

        $this->update();

        $moved = false;
        $vector = $this->getVector($direction);
        $traversals = $this->buildTraversals($vector);

        // Prepare tiles for movement
        foreach($this->tiles as $coord => $tile) {
            $this->tiles[$coord]["previousPosition"] = ["x" => $tile["x"], "y" => $tile["y"]];
        }

        foreach($traversals["x"] as $x) {
            foreach($traversals["y"] as $y) {
                $cell = ["x" => $x, "y" => $y];
                $tile = $this->getCellTile($cell);

                if(isset($tile)) {
                    $positions = $this->findFarthestPosition($cell, $vector);
                    $next = $this->getCellTile($positions["next"]);

                    // Only one merger per row traversal?
                    if(isset($next) && $next["value"] == $tile["value"] && empty($next["mergedFrom"])) {
                        $this->removeTile($tile);

                        // Converge the two tiles' positions
                        $tile["x"] = $next["x"];
                        $tile["y"] = $next["y"];

                        $merged = $positions["next"];
                        $merged["value"] = $tile["value"]*2;
                        $merged["mergedFrom"] = [$tile, $next];

                        $this->setTile($merged);

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
            $this->moves++;

            if(!$this->movesAvailable()) {
                $this->game_over = true;
            }

        }

        $export = $this->export();
        $this->save();
        return ["grid" => $export, "game_over" => $this->game_over];
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
            $this->getCellTile($cell) == null // check if next cell is occupied
        );

        return [
            "farthest" => $previous,
            "next" => $cell // Used to check if a merge is required
        ];
    }

    /**
     * Get the tile at the specified cell
     *
     * @param array $cell Cell in question
     * @return array|null tile if is filled, null otherwise
     */
    private function getCellTile($cell) {
        $key = $cell["x"] . "-" . $cell["y"];
        if(isset($this->tiles[$key])) return $this->tiles[$key];
        else return null;
    }

    /**
     * Add a random tile to to a random empty cell
     */
    private function addRandomTile() {
        $cells = $this->getEmptyCells();
        if (count($cells) > 0) {
            $value = mt_rand() / mt_getrandmax() < 0.9 ? 2 : 4;
            $tile = $cells[array_rand($cells)];
            $tile["value"] = $value;

            $this->tiles[$tile["x"] . "-" . $tile["y"]] = $tile;
        }
    }

    /**
     * Empty the cell at the tile's coordinates
     *
     * @param $tile
     */
    private function removeTile($tile) {
        unset($this->tiles[$tile["x"] . "-" . $tile["y"]]);
    }

    /**
     * Put the tile in the cell at it's coordinates
     *
     * @param $tile
     */
    private function setTile($tile) {
        $this->tiles[$tile["x"] . "-" . $tile["y"]] = $tile;
    }

    /**
     * Move tile to given cell
     *
     * @param $tile
     * @param $cell
     */
    private function moveTile(&$tile, $cell) {
        $this->removeTile($tile);

        $tile["x"] = $cell["x"];
        $tile["y"] = $cell["y"];

        $this->setTile($tile);
    }

    /**
     * Get all empty cells
     *
     * @return array array of empty cells
     */
    private function getEmptyCells() {
        $empty_cells = self::allCells($this);
        for($x = 0; $x < $this->size; $x++) {
            for($y = 0; $y < $this->size; $y++) {
                if(isset($this->tiles["$x-$y"])) unset($empty_cells["$x-$y"]);
            }
        }

        return $empty_cells;
    }

    /**
     * Check if there are any moves left
     *
     * @return bool true if there are moves available, false otherwise
     */
    private function movesAvailable() {
        return (
            count($this->getEmptyCells()) > 0 || // if there are empty cells, game's not over yet
            $this->hasTileMatches() // matches can be made, only check if there are no empty cells
        );
    }

    /**
     * Check whether or not any matches can be made (expensive)
     *
     * @return bool true if at least one matches can be made, false otherwise
     */
    private function hasTileMatches() {
        foreach($this->tiles as $tile) {
            for($direction = 0; $direction < 4; $direction++) {
                $vector = $this->getVector($direction);
                $testTile = $this->getCellTile(["x" => $tile["x"] + $vector["x"], "y" => $tile["y"] + $vector["y"]]);

                if($tile["value"] == $testTile["value"]) {
                    // These two tiles can be merged
                    return true;
                }
            }
        }

      return false;
    }

}