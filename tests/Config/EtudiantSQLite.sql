create table etudiant
(
    id           integer not null
                 constraint id
                 primary key autoincrement,
    nom          text,
    prenom       text,
    sexe         integer,
    anniversaire TEXT
);

INSERT INTO etudiant (id, nom, prenom, sexe, anniversaire) VALUES (1, 'Robin', 'Paul', 1, '2000-12-29');
INSERT INTO etudiant (id, nom, prenom, sexe, anniversaire) VALUES (2, 'Nyan', 'Jack', 1, '2013-08-12');
