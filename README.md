# Futbola kluba mājaslapa

## Par projektu
Šis projekts ir izstrādāts PHP valodas kursa "PHP valoda interaktīvo Web-lietojumu izstrādei(1)" ietvaros, 2024./2025. mācību gada rudens semestrī.

**Autors:** Linass Jokšass  
**ORTUS ID:** 221RDB522  
**Kurss:** 3. kurss  
**Universitāte:** Rīgas Tehniskā universitāte  

## Projekta repozitorijs
- HTTPS: `https://github.com/Brakonabric/php-coursework.git`
- SSH: `git@github.com:Brakonabric/php-coursework.git`
- GitHub CLI: `gh repo clone Brakonabric/php-coursework`
- Vai apmeklējiet: [https://github.com/Brakonabric/php-coursework](https://github.com/Brakonabric/php-coursework)

## Attēlu avots
Visi projektā izmantotie attēli ir ņemti no bezmaksas attēlu krātuves [Pexels](https://www.pexels.com/search/soccer/). 

## Projekta uzstādīšana

### Windows sistēmā

1. **Docker Desktop uzstādīšana:**
   - Lejupielādējiet Docker Desktop no [oficiālās vietnes](https://www.docker.com/products/docker-desktop)
   - Palaidiet lejupielādēto failu un sekojiet instalācijas norādēm
   - Pēc instalācijas restartējiet datoru
   - Pārbaudiet vai WSL 2 ir uzstādīts, ja nē:
     ```bash
     wsl --install
     ```
   - Pārbaudiet Docker instalāciju:
     ```bash
     docker --version
     docker-compose --version
     ```

2. **Git uzstādīšana:**
   - Lejupielādējiet Git no [oficiālās vietnes](https://git-scm.com/download/win)
   - Instalējiet ar noklusējuma iestatījumiem
   - Pārbaudiet instalāciju:
     ```bash
     git --version
     ```

3. **Projekta uzstādīšana:**
   ```bash
   # Klonējiet projektu (izvēlieties vienu no variantiem):
   
   # HTTPS
   git clone https://github.com/Brakonabric/php-coursework.git
   
   # vai SSH (ja iestatīta SSH atslēga)
   git clone git@github.com:Brakonabric/php-coursework.git
   
   # vai izmantojot GitHub CLI
   gh repo clone Brakonabric/php-coursework

   # Pārejiet uz projekta mapi
   cd php-coursework

   # Palaidiet Docker Desktop
   # Pagaidiet līdz Docker Desktop ir pilnībā palaists

   # Palaidiet konteinerus
   docker-compose up -d

   # Projekts būs pieejams
   http://localhost:8080
   ```

### Linux sistēmā

1. **Docker uzstādīšana:**
   ```bash
   # Atjauniniet pakotņu sarakstu
   sudo apt update

   # Instalējiet nepieciešamās pakotnes
   sudo apt install -y apt-transport-https ca-certificates curl software-properties-common

   # Pievienojiet Docker oficiālo GPG atslēgu
   curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

   # Pievienojiet Docker repozitoriju
   echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

   # Atjauniniet pakotņu sarakstu
   sudo apt update

   # Instalējiet Docker
   sudo apt install -y docker-ce docker-ce-cli containerd.io

   # Instalējiet Docker Compose
   sudo apt install -y docker-compose

   # Pievienojiet savu lietotāju docker grupai
   sudo usermod -aG docker $USER

   # Pārstartējiet Docker servisu
   sudo systemctl start docker
   sudo systemctl enable docker

   # Pārbaudiet instalāciju
   docker --version
   docker-compose --version
   ```

2. **Git uzstādīšana:**
   ```bash
   sudo apt install git
   git --version
   ```

3. **Projekta uzstādīšana:**
   ```bash
   # Klonējiet projektu (izvēlieties vienu no variantiem):
   
   # HTTPS
   git clone https://github.com/Brakonabric/php-coursework.git
   
   # vai SSH (ja iestatīta SSH atslēga)
   git clone git@github.com:Brakonabric/php-coursework.git
   
   # vai izmantojot GitHub CLI
   gh repo clone Brakonabric/php-coursework

   # Pārejiet uz projekta mapi
   cd php-coursework

   # Palaidiet konteinerus
   docker-compose up -d

   # Projekts būs pieejams
   http://localhost:8080
   ```

### Datubāzes uzstādīšana

1. Atveriet phpMyAdmin: `http://localhost:8081`
2. Lietotājvārds: `root`
3. Parole: `root`
4. Importējiet datubāzes failu no `sql/database_dump.sql`

### Piezīmes
- Pārliecinieties, ka ports 8080 un 8081 nav aizņemti
- Ja rodas problēmas ar atļaujām Linux sistēmā, izmantojiet `sudo` komandu
- Pārliecinieties, ka Docker serviss ir palaists
- Pēc Docker grupas pievienošanas Linux sistēmā, izejiet no sistēmas un ieejiet no jauna

### Biežāk sastopamās problēmas

1. **WSL 2 nav uzstādīts (Windows):**
   ```bash
   wsl --install
   ```

2. **Ports jau ir izmantots:**
   ```bash
   # Pārbaudiet kurš process izmanto portu
   sudo lsof -i :8080
   sudo lsof -i :8081
   
   # Vai Windows
   netstat -ano | findstr :8080
   netstat -ano | findstr :8081
   ```

3. **Docker nav palaists:**
   ```bash
   # Linux
   sudo systemctl start docker
   
   # Windows
   # Palaidiet Docker Desktop no Start izvēlnes
   ```

## Konteineru pārstartēšana
```bash
# Apturēt konteinerus
docker-compose down

# Palaist konteinerus
docker-compose up -d

# Apskatīt konteineru statusu
docker ps

# Apskatīt konteineru logus
docker-compose logs
``` 