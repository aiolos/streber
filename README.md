# Strebertje!
Fetch data from Strava API and show it

**This is a very early version** 

Functionality is limited and not thoroughly tested.

## Installation:
- Make a checkout of this repo
- Set the right credentials in you `.env` file
- `docker-compose up`
- Enter the docker container:
  - Run `composer install`
- Access application on [http://localhost:8085/]()
- Connect with Strava
- View you statistics, activities and segments

## Todo:
- Use database to save information
- Keep Strava authentication token stored at user
- Add user to database
- Create year in Strava with full polyline (not summary)
- Add D3js for graphs
