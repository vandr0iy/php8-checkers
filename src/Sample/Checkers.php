#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Checkers;

class Game
{
  // this is mirrored top-down
  private $board = [
    [1,0,1,0,1,0,1,0],
    [0,1,0,1,0,1,0,1],
    [1,0,1,0,1,0,1,0],
    [0,0,0,0,0,0,0,0],
    [0,0,0,0,0,0,0,0],
    [0,2,0,2,0,2,0,2],
    [2,0,2,0,2,0,2,0],
    [0,2,0,2,0,2,0,2]
  ];

  // for pretty printing the fields' names
  const COLUMNS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

  // directions for the respective players
  const DIRECTIONS = [1, -1];

  // routes
  const LEFT = -1;
  const RIGHT = 1;

  // turn counter
  private $turn = 0;

  // if this is not 0 - victory has been achieved
  private $winner = 0;


  //pretty print the full board
  public function print() {
    echo "┌───┬───┬───┬───┬───┬───┬───┬───┬───┐\n";
    for ($i = count($this->board) - 1; $i >= 0; $i--) {
      $j = $i + 1;
      echo "│ {$j} │";

      //https://altcodeunicode.com/alt-codes-die-checkers-shogi-symbols/
      foreach ($this->board[$i] as &$field) {
        switch ($field) {
          case 0:
            $x=" "; break;
          case 1:
            $x="⛀"; break;
          case 2:
            $x="⛂"; break;
          case 3:
            $x="⛉"; break;
          case 4:
            $x="⛊"; break;
          default:
            echo "Error during board display! Invalid value: {$field}";
            exit(1);
        }
        echo " {$x} ";
        echo "│";
      }
      echo "\n";

      // https://www.php.net/manual/en/function.next.php
      if (!next($this->board)) {
        echo "└───┼───┼───┼───┼───┼───┼───┼───┼───┤\n";
        echo "    │ A │ B │ C │ D │ E │ F │ G │ H │\n";
        echo "    └───┴───┴───┴───┴───┴───┴───┴───┘\n";
      } else {
        echo "├───┼───┼───┼───┼───┼───┼───┼───┼───┤\n";
      }
    }
  }

  public static function pretty_print_field($i, $j) {
    echo self::COLUMNS[$j];
    echo $i+1;
    echo "={$i}${j}";
  }

  //current player. Either 1 or 2.
  public static function get_current_player($turn) {
    return $turn % 2 + 1;
  }

  //that's one dumb way of doing it
  public static function get_opponent($player) {
    switch ($player) {
      case 2:
        return 1; break;
      case 1:
        return 2; break;
      default:
        echo "Error during opponent retrieval! Invalid value: {$player}";
        exit(2);
    }
  }

  public static function get_queen($player) {
    return $player + 2;
  }

  //current players direction. Either 1 or -1
  public static function get_current_players_direction($player) {
    return self::DIRECTIONS[($player - 1)];
  }

  // functions concerning basic moving logic
  // 1. First - you list given players' pawns, save them all to $current_pawns
  public function list_players_pawns($turn) {

    $player = self::get_current_player($turn);
    $players_pawns = array();

    for ($i = 0; $i < count($this->board); $i++) { //rows
      for ($j = 0; $j < count($this->board[$i]); $j++) { //columns

        $current=$this->board[$i][$j];
        //echo "current:{$current} ";

        if ($current !== 0 && $current % 2 === $player) {

          // print every checker
          self::pretty_print_field($i, $j);
          echo ", ";
          array_push($players_pawns, [$i, $j]);
        }
      }
    }
    echo "\n";
    return $players_pawns;
  }

  public static function get_route($i, $j, $route, $direction) {
    return [$i+$direction, $j+$route];
  }

  public static function check_if_exists($i, $j, $board) {
    return array_key_exists($i, $board) && array_key_exists($j, $board[$i]);
  }

  public static function check_for_chain($starting_field, $current_field, $player, $board, $route, &$chain) {
    $direction = self::get_current_players_direction($player);

    [$x, $y] = $starting_field;
    [$i, $j] = $current_field;

    //opponent's pawn
    $ri = $i + $direction;
    $rj = $j + $direction;

    //destination of the jump; right behind opponent's pawn
    $rri = $i + 2 * $direction;
    $rrj = $j + 2 * $route;

    // if the jump's destination is present on the board and it happens over the opponent's pawn
    if (self::check_if_exists($rri, $rrj, $board) &&
        $board[$ri][$rj] === self::get_opponent($player)) {
      $old_chain = $chain["{$x},{$y}-{$i}{$j}"];

      // if the starting_field->current_field jump is already in the chain:
      // - take the list of the captured pawns
      // - add the new one
      // - remove the old entry
      // - insert it with the new destination and the new pawn added
      // OTHERWISE
      // - just create a new entry with only one pawn.
      if (is_array($old_chain) &&
          array_key_exists(2, $old_chain) &&
          is_array($old_chain[2])) {
        $chain["{$x},{$y}-{$i}{$j}"] = null;
        $chain["{$x},{$y}-{$rri}{$rrj}"] = [[$x, $y], [$rri, $rrj], array_push($old_chain[2], [$ri, $rj])];
      } else {
        $chain["{$x},{$y}-{$rri}{$rrj}"] = [[$x, $y], [$rri, $rrj], [[$ri, $rj]]];
      }
      // TODO recur left
      // TODO recur right
      return $chain;
    }
  }

  // 2. Then - you check all the possible moves. Just look one row forward, then left + right
  public function check_possible_moves($turn) {
    $player = self::get_current_player($turn);
    $player_queen = self::get_queen($player);

    $opponent = self::get_opponent($player);
    $opponent_queen = self::get_queen($opponent);

    $players_pawns = $this->list_players_pawns($turn);
    $direction = self::get_current_players_direction($player);

    $possible_moves = array();
    $obligatory_capture = false;

    foreach ($players_pawns as &$field) {
      [$i, $j] = $field;
      foreach ([self::LEFT, self::RIGHT] as $route) {
        [$ri, $rj] = self::get_route($i, $j, $route, $direction);

        if (self::check_if_exists($ri, $rj, $this->board)) {
          switch ($this->board[$ri][$rj]) {
            case 0:
              if (!$obligatory_capture) {
                array_push($possible_moves, [[$i, $j], [$ri, $rj]]); //TODO use same format as above
              }
              break;
            case $opponent:
            case $opponent_queen:
              // if we find an opponents' pawn on our way - then the capture is obligatory
              // in this case - we clear out all the moves we considered so far
              // and only focus on finding out what can we capture
              if (!$obligatory_capture) {
                $obligatory_capture = true;
                $possible_moves = array();
              }
              $beating_chain = function ( $n ) use ( &$beating_chain ) {
                if (self::check_if_exists($ri + $direction, $rj + $route, $this->board)) {
                  //TODO: create a data structure for this
                  //TODO: differentiate that in the display logic
                  //TODO: a recursive lambda should help in writing this one
                  //TODO: it's not possible to stop eating pawns!
                }
              };
            case $player:
            case $player_queen:
              break;
            default:
              echo "Error during moves retrieval! Invalid values: {$ri}, {$rj}, {$this->board[$ri][$rj]}";
              exit(3);
          }
        }
      }
    }
  }

  public function main() {
    $this->print();
    $arst = ['pawn' => [3, 7], 'pawn' => [1, 2], 'goesto' => ['a', 'b']];
    print_r($arst);
    //$this->check_possible_moves($this->turn);
  }

}

$match = new Game();
$match->main();

?>
