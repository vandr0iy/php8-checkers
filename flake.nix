{
  description = "Nix flake";

  inputs = {
    flake-utils.url = "github:numtide/flake-utils";
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    devshell = {
      url = "github:numtide/devshell/master";
      inputs.nixpkgs.follows = "nixpkgs";
      inputs.flake-utils.follows = "flake-utils";
    };
  };
  outputs = {
    self,
    devshell,
    nixpkgs,
    flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = import nixpkgs {
          inherit system;
          config = { allowUnfree = true; };
          overlays = [ devshell.overlay ];
        };
      in {
        devShell = pkgs.devshell.mkShell {
          name = "PHP8-dev-shell";
          commands = [
            {
              name = "unittest";
              category = "repl";
              help = "Run unit tests with phpunit";
              command = "./vendor/bin/phpunit";
            }
            {
              name = "cinst";
              category = "repl";
              help = "short for 'composer install'";
              command = "composer install";
            }
            {
              name = "cupd";
              category = "repl";
              help = "short for 'composer update'";
              command = "composer update";
            }
          ];
          packages = with pkgs; [
            php81
            php81Packages.composer
          ];
        };
      }
    );
}
