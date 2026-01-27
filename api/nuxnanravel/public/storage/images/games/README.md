# Game Images

This directory is for storing game thumbnail images that will be displayed on the Game Dashboard.

## Image Requirements

- Recommended size: 400x300 pixels (4:3 aspect ratio)
- Format: SVG, JPG, PNG, or WebP
- File size: Keep under 200KB for optimal loading

## Naming Convention

Use the following naming convention for game images:

- `guessing-number.jpg` - For the Number Guessing Game
- `xo-game.jpg` - For the OX Game
- `snake-game.jpg` - For the Snake Game
- `mental-math.jpg` - For the Mental Math Game

## How Images Are Used

The Game Dashboard component (`resources/js/Pages/Play/Games/GameDashboard.vue`) references these images using the `/storage/images/games/` path, which is accessible through Laravel's storage link.

If an image is not available or fails to load, the dashboard will automatically fall back to displaying the game's emoji icon.

## Adding New Games

When adding new games to the dashboard:

1. Add the game image to this directory following the naming convention
2. Update the `games` array in `GameDashboard.vue` with the correct thumbnail path
3. The image will be displayed automatically on the game card