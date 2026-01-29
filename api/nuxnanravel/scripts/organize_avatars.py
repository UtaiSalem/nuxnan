import os
import shutil
import argparse
import re
import datetime
import logging
import sys

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)

def get_week_folder_name(timestamp):
    """
    Returns a folder name in format 'YYYY_WeekWW' based on the timestamp.
    """
    dt = datetime.datetime.fromtimestamp(timestamp)
    year, week, weekday = dt.isocalendar()
    return f"{year}_Week{week:02d}"

def parse_timestamp_from_filename(filename):
    """
    Attempts to extract timestamp from filename format: {userid}_{timestamp}_{uniqid}.ext
    Returns timestamp (float) or None if not found.
    """
    # Pattern: number_number_string.ext
    # We are interested in the second number group which represents timestamp
    match = re.search(r'\d+_(\d+)_\w+', filename)
    if match:
        try:
            return float(match.group(1))
        except ValueError:
            return None
    return None

def organize_images(source_dir, dest_dir, dry_run=False, move=True):
    """
    Scans source_dir and organizes images into dest_dir/YYYY_WeekWW/
    """
    if not os.path.exists(source_dir):
        logging.error(f"Source directory does not exist: {source_dir}")
        return

    if not os.path.exists(dest_dir):
        if dry_run:
            logging.info(f"[DRY-RUN] Would create destination directory: {dest_dir}")
        else:
            try:
                os.makedirs(dest_dir)
                logging.info(f"Created destination directory: {dest_dir}")
            except OSError as e:
                logging.error(f"Failed to create destination directory: {e}")
                return

    logging.info(f"Scanning source: {source_dir}")
    logging.info(f"Target destination: {dest_dir}")
    if dry_run:
        logging.info("--- RUNNING IN DRY-RUN MODE (No changes will be made) ---")

    files_processed = 0
    files_moved = 0
    errors = 0

    for root, dirs, files in os.walk(source_dir):
        for filename in files:
            # Skip non-image files if needed, or process all. 
            # For now, let's process common image extensions.
            if not filename.lower().endswith(('.jpg', '.jpeg', '.png', '.gif', '.webp')):
                continue

            files_processed += 1
            source_path = os.path.join(root, filename)
            
            # Determine timestamp
            timestamp = parse_timestamp_from_filename(filename)
            
            # Fallback to file modification time
            if timestamp is None:
                try:
                    timestamp = os.path.getmtime(source_path)
                    logging.debug(f"Using file mtime for {filename}")
                except OSError:
                    logging.warning(f"Could not get mtime for {filename}, skipping.")
                    errors += 1
                    continue
            
            # Determine destination folder
            week_folder = get_week_folder_name(timestamp)
            target_folder = os.path.join(dest_dir, week_folder)
            target_path = os.path.join(target_folder, filename)

            if dry_run:
                logging.info(f"[DRY-RUN] Would {'move' if move else 'copy'} {filename} -> {week_folder}/{filename}")
            else:
                try:
                    # Create week folder if not exists
                    if not os.path.exists(target_folder):
                        os.makedirs(target_folder)
                    
                    # Move or Copy
                    if move:
                        shutil.move(source_path, target_path)
                        logging.info(f"Moved: {filename} -> {week_folder}/")
                    else:
                        shutil.copy2(source_path, target_path)
                        logging.info(f"Copied: {filename} -> {week_folder}/")
                    
                    files_moved += 1
                except Exception as e:
                    logging.error(f"Error processing {filename}: {e}")
                    errors += 1

    logging.info("-" * 30)
    logging.info(f"Processing Complete.")
    logging.info(f"Total Files Scanned: {files_processed}")
    logging.info(f"Total Files {'Moved' if move else 'Copied'}: {files_moved}")
    logging.info(f"Errors: {errors}")
    if dry_run:
        logging.info("End of Dry Run.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Organize avatar images into weekly folders based on date.")
    
    parser.add_argument("--source", required=True, help="Path to the source directory containing images")
    parser.add_argument("--dest", required=True, help="Path to the destination directory")
    parser.add_argument("--dry-run", action="store_true", help="Simulate the operation without moving files")
    parser.add_argument("--copy", action="store_true", help="Copy files instead of moving (default is move)")

    args = parser.parse_args()

    organize_images(
        source_dir=args.source,
        dest_dir=args.dest,
        dry_run=args.dry_run,
        move=not args.copy
    )
