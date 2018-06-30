# Strebertje!
Fetch data from Strava API and show it

**This is a very early version** 

Functionality is limited and not thoroughly tested.

## Installation:
- Make a checkout of this repo
- Set the right credentials in you `.env` file
  - And make the database available
- `docker-compose up`
- Enter the docker container:
  - Run `composer install`
- Access application on [http://localhost:8085/]()
- Register an account
- Connect with Strava
- View you statistics, activities and segments

## Todo:
- Use database to save information (like activities, gear and analysis)
- Create year in Strava with full polyline (not summary)
- Add more graphs / combine data (Plotly using D3js is used for graphs)
