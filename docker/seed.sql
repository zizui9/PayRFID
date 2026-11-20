-- Données de démonstration (mot de passe de tous les comptes : demo1234)
INSERT INTO users (username, password, nom, prenom, role, badge_uid, solde) VALUES
 ('admin',    '$2y$10$.MdeDypZyoBuOram8Sb2KeZRS/fx5kn2hFQTPaeaUkTFFagspgpYa', 'Administrateur', 'Système', 'admin',       'AD000001', 0),
 ('boulange', '$2y$10$.MdeDypZyoBuOram8Sb2KeZRS/fx5kn2hFQTPaeaUkTFFagspgpYa', 'Martin',         'Léa',     'commercant',  'C0000001', 0),
 ('client1',  '$2y$10$.MdeDypZyoBuOram8Sb2KeZRS/fx5kn2hFQTPaeaUkTFFagspgpYa', 'Durand',         'Paul',    'utilisateur', 'A1B2C3D4', 0);
