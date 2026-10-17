---
id: update-version-changelog
name: Mettre à jour la version et le CHANGELOG
description: Guide pour mettre à jour la version du framework Edu Framework dans eduframe.yml, properdocs.yml et CHANGELOG.md, puis déployer automatiquement via GitHub Actions.
---

# Skill : Mettre à jour la version et le CHANGELOG

## 📌 Description
Ce skill permet de **mettre à jour la version du framework Edu Framework** et de **générer une nouvelle entrée dans le CHANGELOG** de manière structurée. Il couvre :
- La mise à jour de `eduframe.yml` (version interne du framework).
- La mise à jour de `properdocs.yml` (version affichée sur le site GitHub Pages).
- L'ajout d'une nouvelle section dans `CHANGELOG.md`.
- Le déploiement automatique via GitHub Actions.

---

## 🚀 Prérequis
- Être dans le dépôt `studoo-app/edu-framework`.
- Avoir les droits pour pousser sur la branche `main`.
- Avoir `git` et un éditeur de texte configurés.

---

## 📝 Étapes pour mettre à jour la version

### 1️⃣ Vérifier la version actuelle
Exécutez cette commande pour voir les versions actuelles :
```bash
grep -E "version:|default:" eduframe.yml properdocs.yml
```
Exemple de sortie :
```
eduframe.yml:version: 'v2.7.0'
properdocs.yml:    default: v2.7.0
```

---

### 2️⃣ Choisir la nouvelle version
- Suivez le format **`vX.Y.Z`** (ex: `v2.7.0` → `v2.8.0`).
- Pour une **majeure** (changements incompatibles) : `v3.0.0`.
- Pour une **mineure** (nouvelles fonctionnalités) : `v2.8.0`.
- Pour un **patch** (corrections de bugs) : `v2.7.1`.

---

### 3️⃣ Mettre à jour `eduframe.yml`
Ouvrez le fichier `eduframe.yml` et modifiez les champs suivants :
```yaml
# La version du framework
version: 'v2.8.0'          # ← Mettre à jour ici

# La date de livraison (format YYYY-MM-DD)
date_version: '2026-10-17' # ← Mettre à jour avec la date du jour
```

---

### 4️⃣ Mettre à jour `properdocs.yml`
Ouvrez le fichier `properdocs.yml` et modifiez la version affichée sur le site :
```yaml
extra:
  version:
    default: v2.8.0       # ← Mettre à jour ici
```

---

### 5️⃣ Mettre à jour `CHANGELOG.md`
Ajoutez une **nouvelle section en haut du fichier** (juste après le titre `# CHANGELOG`) :

```markdown
## v2.8.0 - 17/10/2026

**new features**

- [#200](https://github.com/studoo-app/edu-framework/pull/200) Ajout du support de PHP 8.5 @bfoujols
- Nouvelle fonctionnalité X pour améliorer Y.

**bug Fixes**

- [#199](https://github.com/studoo-app/edu-framework/issues/199) Correction du bug Z dans le module A.

**documentation**

- new [Nouvelle documentation](docs/chemin/vers/la/doc.md) : description de la nouvelle page.
- update [Documentation existante](docs/chemin/vers/la/doc.md) : mise à jour pour refléter les changements.
```

#### 📌 Règles pour le CHANGELOG :
- **Format de la date** : `JJ/MM/AAAA` (ex: `17/10/2026`).
- **Liens GitHub** : Toujours inclure le numéro de l'issue ou de la PR (ex: `[#200](lien)`).
- **Catégories** : Utilisez `new features`, `bug Fixes`, et `documentation`.
- **Ordre** : Les sections doivent être **triées par date décroissante** (la plus récente en premier).

---

### 6️⃣ Valider les changements avec Git
```bash
# Ajouter les fichiers modifiés
git add eduframe.yml properdocs.yml CHANGELOG.md

# Faire un commit avec un message clair
git commit -m "chore: bump version to v2.8.0"

# Pousser sur la branche main
git push origin main
```

---

### 7️⃣ Déclencher le déploiement du site
- Le workflow GitHub Actions (`documentation.yml`) **se lance automatiquement** après un push sur `main`.
- **Vérifiez son exécution** : [https://github.com/studoo-app/edu-framework/actions/workflows/documentation.yml](https://github.com/studoo-app/edu-framework/actions/workflows/documentation.yml)
- **Attendez 5-10 minutes** pour que le site soit mis à jour.

---

### 8️⃣ Vérifier la mise à jour du site
- **Site** : [https://studoo-app.github.io/edu-framework/](https://studoo-app.github.io/edu-framework/)
- **Vérifiez** que la version affichée correspond bien à la nouvelle version (ex: `v2.8.0`).

---

## ⚡ Script Bash pour automatiser (Optionnel)

### Script `bump-version.sh`
Créez un fichier `bump-version.sh` à la racine du projet :
```bash
#!/bin/bash
# Usage: ./bump-version.sh v2.8.0

set -e

NEW_VERSION=$1
TODAY=$(date +%Y-%m-%d)
TODAY_FR=$(date +%d/%m/%Y)

if [ -z "$NEW_VERSION" ]; then
    echo "Erreur : Veuillez spécifier la nouvelle version (ex: v2.8.0)"
    exit 1
fi

# Mettre à jour eduframe.yml
sed -i.bak "s/version: '.*'/version: '$NEW_VERSION'/" eduframe.yml
sed -i.bak "s/date_version: '.*'/date_version: '$TODAY'/" eduframe.yml
rm -f eduframe.yml.bak

# Mettre à jour properdocs.yml
sed -i.bak "s/default: .*/default: $NEW_VERSION/" properdocs.yml
rm -f properdocs.yml.bak

# Ajouter l'en-tête du CHANGELOG
{
    echo "## $NEW_VERSION - $TODAY_FR"
    echo ""
    echo "**new features**"
    echo ""
    echo "**bug Fixes**"
    echo ""
    echo "**documentation**"
    echo ""
    cat CHANGELOG.md
} > CHANGELOG.md.tmp && mv CHANGELOG.md.tmp CHANGELOG.md

# Valider et pousser
git add eduframe.yml properdocs.yml CHANGELOG.md
git commit -m "chore: bump version to $NEW_VERSION"
git push origin main

echo "✅ Version mise à jour vers $NEW_VERSION et poussée sur main !"
echo "🔄 Le workflow GitHub Actions va se lancer automatiquement..."
```

#### Rendre le script exécutable :
```bash
chmod +x bump-version.sh
```

#### Utilisation :
```bash
./bump-version.sh v2.8.0
```

---

## 🎯 Bonnes pratiques

### ✅ À faire
- **Toujours vérifier** les 3 fichiers (`eduframe.yml`, `properdocs.yml`, `CHANGELOG.md`) avant de pousser.
- **Utiliser des messages de commit clairs** (ex: `chore: bump version to v2.8.0`).
- **Lier les issues/PRs** dans le CHANGELOG pour une traçabilité complète.
- **Tester localement** avec `properdocs serve` avant de pousser si des changements majeurs sont apportés à la documentation.

### ❌ À éviter
- **Oublier de mettre à jour un des 3 fichiers** (la version ne sera pas cohérente).
- **Utiliser des formats de date incohérents** (toujours `JJ/MM/AAAA`).
- **Pousser directement sur `main` sans review** (si le projet l’exige, utilisez une PR).
- **Modifier manuellement la branche `gh-pages`** (elle est gérée par GitHub Actions).

---

## 🔍 Dépannage

### Problème : Le site n'affiche pas la nouvelle version
- **Vérifiez** que le workflow GitHub Actions s'est bien exécuté.
- **Vérifiez** que le commit a bien été poussé sur `main`.
- **Attendez 10 minutes** : le déploiement peut prendre du temps.
- **Vérifiez les logs du workflow** pour voir s'il y a eu une erreur.

### Problème : Le workflow échoue
- **Vérifiez** que `properdocs.yml` est valide (pas de syntaxe YAML incorrecte).
- **Vérifiez** que les dépendances Python sont installées (`pip install -r requirements.txt`).
- **Relancez le workflow manuellement** depuis l'interface GitHub Actions.

---

## 📚 Ressources utiles
- [Convention de versionnement SemVer](https://semver.org/lang/fr/)
- [GitHub Actions pour MkDocs/ProperDocs](https://github.com/marketplace/actions/deploy-mkdocs)
- [Documentation ProperDocs](https://github.com/ProperDocs/properdocs)

---

## 💡 Astuces
- **Utilisez des alias Git** pour accélérer le processus :
  ```bash
  git config --global alias.bump "!f() { git add eduframe.yml properdocs.yml CHANGELOG.md && git commit -m \"chore: bump version to $1\" && git push origin main; }; f"
  ```
  Puis utilisez : `git bump v2.8.0`.

- **Pour générer automatiquement la date** :
  ```bash
  date +%d/%m/%Y  # Format pour le CHANGELOG
  date +%Y-%m-%d  # Format pour eduframe.yml
  ```

---

## 📞 Support
Si vous rencontrez des problèmes, vérifiez :
1. Les **logs du workflow GitHub Actions**. 
2. Les **fichiers modifiés** (`git diff`).
3. La **branche `main`** (`git status`).

Ou demandez de l'aide sur le dépôt GitHub : [studoo-app/edu-framework](https://github.com/studoo-app/edu-framework).
