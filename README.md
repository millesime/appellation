Appellation
===========

Add your archives built with [Millesime](https://millesime.io/) to your releases assets into your DevOps lifecycle tool.

See https://appellation.millesime.io/ for more information and documentation.


```
appellation publish millesime/millesime 1.0.0-BETA2 -u githubuser -p githubtoken
```

Docker
------

Execute inside a docker container :
```
docker build -t appellation .
docker run -it --rm appellation publish millesime/millesime 1.0.0-BETA2 -u githubuser -p githubtoken
```
