<?php
return [
    // Page meta
    'page_description_realm' => 'Configuration du royaume pour le serveur WoW Sahtout',
    'page_title_realm' => 'Configuration du royaume',

    // Errors
    'err_fix_errors' => 'Veuillez corriger les erreurs suivantes :',
    'err_invalid_csrf' => 'Jeton CSRF invalide.',
    'err_realm_name_required' => 'Le nom du royaume est requis.',
    'err_realm_name_length' => 'Le nom du royaume doit contenir 32 caractères ou moins.',
    'err_realm_ip_required' => 'L\'adresse publique est requise.',
    'err_realm_ip_invalid' => 'L\'adresse publique est invalide.',
    'err_realm_local_address_required' => 'L\'adresse locale est requise.',
    'err_realm_local_address_invalid' => 'L\'adresse locale contient des caractères invalides.',
    'err_realm_check_address_required' => 'L\'adresse de vérification du statut est requise.',
    'err_realm_check_address_invalid' => 'L\'adresse de vérification du statut contient des caractères invalides.',
    'err_realm_port_invalid' => 'Le port du royaume doit être un nombre valide (1-65535).',
    'err_realm_db_unavailable' => 'La connexion à la base de données d\'authentification est indisponible.',
    'err_realm_db_save' => 'Échec de l\'enregistrement des paramètres du royaume dans la base de données d\'authentification.',
    'error_realm_logo_too_large' => 'La taille du logo du royaume dépasse 2 Mo.',
    'error_invalid_realm_logo_type' => 'Type de fichier invalide. Seuls PNG, JPG ou WebP sont autorisés.',
    'error_realm_logo_upload_failed' => 'Le répertoire de téléchargement n\'est pas accessible ou accessible en écriture.',
    'err_config_dir_not_writable' => 'Le répertoire de configuration n\'est pas accessible en écriture : %s',
    'err_write_realm_config' => 'Impossible d\'écrire le fichier de configuration du royaume : %s',

    // Success
    'msg_realm_saved' => 'Configuration du royaume enregistrée avec succès !',

    // Section titles
    'section_realm_config' => 'Configuration du royaume',

    // Labels
    'label_current_logo' => 'Logo actuel',
    'label_realm_name' => 'Nom du royaume',
    'placeholder_realm_name' => 'Entrez le nom du royaume',
    'label_realm_ip' => 'Adresse publique',
    'label_realm_local_address' => 'Adresse locale',
    'label_realm_check_address' => 'Adresse de vérification du statut',
    'placeholder_realm_address' => 'play.example.com',
    'placeholder_realm_local_address' => '127.0.0.1',
    'placeholder_realm_check_address' => '127.0.0.1',
    'note_realm_local_address' => 'Utilisée par le serveur d\'authentification pour les joueurs locaux/LAN (realmlist.localAddress), généralement 127.0.0.1 ou une IP LAN.',
    'note_realm_check_address' => 'Utilisée en interne par le site pour vérifier si le serveur est en ligne. Généralement 127.0.0.1 lorsque le site et le serveur de jeu sont sur la même machine.',
    'label_realm_port' => 'Port du royaume',
    'label_player_display' => 'Affichage des joueurs',
    'player_display_all' => 'Tous les joueurs',
    'player_display_all_desc' => 'Afficher les humains et les bots ensemble comme un seul nombre de joueurs.',
    'player_display_separate' => 'Humains / Bots',
    'player_display_separate_desc' => 'Afficher les vrais joueurs et les playerbots comme des comptes distincts.',
    'label_realm_logo' => 'Logo du royaume',
    'placeholder_realm_logo' => 'Cliquez ou glissez pour télécharger un nouveau logo',
    'note_realm_logo' => 'Téléchargez un nouveau logo pour votre royaume. Laissez vide pour conserver le logo actuel.',
    'note_realm_config' => 'Ceci configure les paramètres d\'un seul royaume.',

    // Buttons
    'btn_save_realm' => 'Enregistrer la configuration du royaume',
];
?>