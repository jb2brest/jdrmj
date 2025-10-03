<?php

/**
 * Classe User - Gestion des utilisateurs
 * 
 * Cette classe encapsule toutes les fonctionnalités liées aux utilisateurs
 * du système JDR MJ, incluant l'authentification, les rôles et les permissions.
 */
class User
{
    private $id;
    private $username;
    private $email;
    private $role;
    private $isDm;
    private $createdAt;
    private $updatedAt;
    private $pdo;

    /**
     * Constructeur
     * 
     * @param PDO $pdo Instance PDO pour la base de données (optionnel)
     * @param array $data Données de l'utilisateur (optionnel)
     */
    public function __construct(PDO $pdo = null, array $data = [])
    {
        $this->pdo = $pdo ?: getPDO();
        
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * Hydrate l'objet avec des données
     * 
     * @param array $data Données de l'utilisateur
     */
    private function hydrate(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->role = $data['role'] ?? 'player';
        $this->isDm = $data['is_dm'] ?? false;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    // =====================================================
    // MÉTHODES D'AUTHENTIFICATION
    // =====================================================

    /**
     * Vérifie si l'utilisateur est connecté
     * 
     * @return bool True si connecté
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Redirige vers la page de connexion si non connecté
     */
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    /**
     * Authentifie un utilisateur
     * 
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @return User|null Utilisateur authentifié ou null
     */
    public function authenticate($username, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $userData = $stmt->fetch();

        if ($userData && password_verify($password, $userData['password_hash'])) {
            $this->hydrate($userData);
            $this->startSession();
            return $this;
        }

        return null;
    }

    /**
     * Démarre une session utilisateur
     */
    public function startSession()
    {
        $_SESSION['user_id'] = $this->id;
        $_SESSION['username'] = $this->username;
        $_SESSION['role'] = $this->role;
        $_SESSION['is_dm'] = $this->isDm;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout()
    {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // =====================================================
    // MÉTHODES DE RÔLES ET PERMISSIONS
    // =====================================================

    /**
     * Obtient le rôle de l'utilisateur actuel
     * 
     * @return string Rôle de l'utilisateur
     */
    public static function getCurrentUserRole()
    {
        // Si le rôle est déjà en session, le retourner
        if (isset($_SESSION['role'])) {
            return $_SESSION['role'];
        }
        
        // Sinon, le récupérer depuis la base de données
        if (isset($_SESSION['user_id'])) {
            global $pdo;
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['role'] = $user['role']; // Mettre en cache
                return $user['role'];
            }
        }
        
        return 'player'; // Par défaut
    }

    /**
     * Vérifie si l'utilisateur actuel est un MJ
     * 
     * @return bool True si MJ
     */
    public static function isDM()
    {
        $role = self::getCurrentUserRole();
        return $role === 'dm' || $role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur actuel est un joueur
     * 
     * @return bool True si joueur
     */
    public static function isPlayer()
    {
        return self::getCurrentUserRole() === 'player';
    }

    /**
     * Vérifie si l'utilisateur actuel est admin
     * 
     * @return bool True si admin
     */
    public static function isAdmin()
    {
        return self::getCurrentUserRole() === 'admin';
    }

    /**
     * Vérifie si l'utilisateur actuel est MJ ou admin
     * 
     * @return bool True si MJ ou admin
     */
    public static function isDMOrAdmin()
    {
        $role = self::getCurrentUserRole();
        return $role === 'dm' || $role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur a des privilèges élevés
     * 
     * @return bool True si privilèges élevés
     */
    public static function hasElevatedPrivileges()
    {
        return self::isDMOrAdmin();
    }
    
    /**
     * Redirige si l'utilisateur n'est pas MJ
     */
    public static function requireDM()
    {
        self::requireLogin();
        if (!self::isDM()) {
            header('Location: profile.php?error=dm_required');
            exit();
        }
    }
    
    /**
     * Redirige si l'utilisateur n'est pas MJ ou admin
     */
    public static function requireDMOrAdmin()
    {
        self::requireLogin();
        if (!self::isDMOrAdmin()) {
            header('Location: profile.php?error=dm_or_admin_required');
            exit();
        }
    }
    
    /**
     * Redirige si l'utilisateur n'est pas admin
     */
    public static function requireAdmin()
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: profile.php?error=admin_required');
            exit();
        }
    }

    // =====================================================
    // MÉTHODES DE GESTION DES UTILISATEURS
    // =====================================================

    /**
     * Trouve un utilisateur par son ID
     * 
     * @param int $id ID de l'utilisateur
     * @return User|null Utilisateur trouvé ou null
     */
    public static function findById($id)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch();

        if ($userData) {
            return new self($pdo, $userData);
        }

        return null;
    }

    /**
     * Trouve un utilisateur par son nom d'utilisateur
     * 
     * @param string $username Nom d'utilisateur
     * @return User|null Utilisateur trouvé ou null
     */
    public static function findByUsername($username)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch();

        if ($userData) {
            return new self($pdo, $userData);
        }

        return null;
    }

    /**
     * Trouve un utilisateur par son email
     * 
     * @param string $email Email de l'utilisateur
     * @return User|null Utilisateur trouvé ou null
     */
    public static function findByEmail($email)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch();

        if ($userData) {
            return new self($pdo, $userData);
        }

        return null;
    }

    /**
     * Obtient l'utilisateur actuellement connecté
     * 
     * @return User|null Utilisateur connecté ou null
     */
    public static function getCurrentUser()
    {
        if (isset($_SESSION['user_id'])) {
            return self::findById($_SESSION['user_id']);
        }
        return null;
    }

    /**
     * Crée un nouvel utilisateur
     * 
     * @param array $data Données de l'utilisateur
     * @return User|null Utilisateur créé ou null en cas d'erreur
     */
    public static function create(array $data)
    {
        // Validation des données requises
        $required = ['username', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Le champ '$field' est requis");
            }
        }

        $pdo = getPDO();

        // Vérifier si l'utilisateur existe déjà
        if (self::findByUsername($data['username'])) {
            throw new InvalidArgumentException("Ce nom d'utilisateur est déjà utilisé");
        }

        if (self::findByEmail($data['email'])) {
            throw new InvalidArgumentException("Cet email est déjà utilisé");
        }

        // Hacher le mot de passe
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Préparer les données
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'role' => $data['role'] ?? 'player',
            'is_dm' => $data['is_dm'] ?? 0
        ];

        // Insérer en base
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role, is_dm, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([
            $userData['username'],
            $userData['email'],
            $userData['password_hash'],
            $userData['role'],
            $userData['is_dm']
        ])) {
            $userData['id'] = $pdo->lastInsertId();
            return new self($pdo, $userData);
        }

        return null;
    }

    /**
     * Met à jour l'utilisateur
     * 
     * @param array $data Nouvelles données
     * @return bool True si mis à jour avec succès
     */
    public function update(array $data)
    {
        $fields = [];
        $values = [];

        if (isset($data['username'])) {
            $fields[] = 'username = ?';
            $values[] = $data['username'];
            $this->username = $data['username'];
        }

        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
            $this->email = $data['email'];
        }

        if (isset($data['password'])) {
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $values[] = $data['role'];
            $this->role = $data['role'];
        }

        if (isset($data['is_dm'])) {
            $fields[] = 'is_dm = ?';
            $values[] = $data['is_dm'];
            $this->isDm = $data['is_dm'];
        }

        if (empty($fields)) {
            return true; // Rien à mettre à jour
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $this->id;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($values);
    }

    /**
     * Supprime l'utilisateur
     * 
     * @return bool True si supprimé avec succès
     */
    public function delete()
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    // =====================================================
    // MÉTHODES DE VÉRIFICATION
    // =====================================================

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     * 
     * @param string $role Rôle à vérifier
     * @return bool True si l'utilisateur a ce rôle
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Vérifie si l'utilisateur est MJ (instance)
     * 
     * @return bool True si MJ
     */
    public function isDmInstance()
    {
        return $this->role === 'dm' || $this->role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur est admin (instance)
     * 
     * @return bool True si admin
     */
    public function isAdminInstance()
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifie si l'utilisateur est joueur (instance)
     * 
     * @return bool True si joueur
     */
    public function isPlayerInstance()
    {
        return $this->role === 'player';
    }

    // =====================================================
    // GETTERS
    // =====================================================

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getIsDm()
    {
        return $this->isDm;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // =====================================================
    // MÉTHODES UTILITAIRES
    // =====================================================

    /**
     * Convertit l'objet en tableau
     * 
     * @return array Données de l'utilisateur
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'is_dm' => $this->isDm,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Obtient le label du rôle en français
     * 
     * @return string Label du rôle
     */
    public function getRoleLabel()
    {
        switch ($this->role) {
            case 'admin':
                return 'Administrateur';
            case 'dm':
                return 'Maître de Jeu';
            case 'player':
                return 'Joueur';
            default:
                return 'Inconnu';
        }
    }

    /**
     * Obtient la couleur du rôle (pour l'affichage)
     * 
     * @return string Couleur Bootstrap du rôle
     */
    public function getRoleColor()
    {
        switch ($this->role) {
            case 'admin':
                return 'danger';
            case 'dm':
                return 'success';
            case 'player':
                return 'primary';
            default:
                return 'secondary';
        }
    }

    /**
     * Obtient le niveau d'expérience en français
     * 
     * @param string $level Niveau d'expérience
     * @return string Label du niveau
     */
    public static function getExperienceLevelLabel($level)
    {
        switch ($level) {
            case 'debutant':
                return 'Débutant';
            case 'intermediaire':
                return 'Intermédiaire';
            case 'expert':
                return 'Expert';
            default:
                return 'Débutant';
        }
    }

    // =====================================================
    // MÉTHODES DE GESTION DES CAMPAGNES
    // =====================================================

    /**
     * Vérifie si l'utilisateur est membre d'une campagne
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $campaignId ID de la campagne
     * @return array|null Informations d'appartenance ou null si pas membre
     */
    public static function isMemberOfCampaign($userId, $campaignId)
    {
        $pdo = getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT cm.role, cm.joined_at 
                FROM campaign_members cm 
                WHERE cm.campaign_id = ? AND cm.user_id = ?
            ");
            $stmt->execute([$campaignId, $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'appartenance à la campagne: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si l'utilisateur est membre d'une campagne (méthode d'instance)
     * 
     * @param int $campaignId ID de la campagne
     * @return array|null Informations d'appartenance ou null si pas membre
     */
    public function isMemberOfCampaignInstance($campaignId)
    {
        return self::isMemberOfCampaign($this->id, $campaignId);
    }

    /**
     * Obtient toutes les campagnes dont l'utilisateur est membre
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des campagnes
     */
    public static function getCampaigns($userId)
    {
        $pdo = getPDO();
        
        try {
            $stmt = $pdo->prepare("
                SELECT c.id, c.title, c.description, c.game_system, cm.role, cm.joined_at
                FROM campaigns c
                JOIN campaign_members cm ON c.id = cm.campaign_id
                WHERE cm.user_id = ?
                ORDER BY c.title ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des campagnes de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtient toutes les campagnes dont l'utilisateur est membre (méthode d'instance)
     * 
     * @return array Liste des campagnes
     */
    public function getCampaignsInstance()
    {
        return self::getCampaigns($this->id, $this->pdo);
    }
}
