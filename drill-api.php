<?php
/**
 * DRILL SCORE APPLICATION API - Version 2.9 Complete Merged
 * Complete PHP backend for the drill scoring system with ALL features:
 * - Journal, Challenge Events, and Assignment Management
 * - File-Based Diagram Management with Image Upload
 * 
 * File: drill-api.php
 * Merged from versions 2.4 and 2.8
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Enable CORS for frontend requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if WordPress is available
if (!defined('ABSPATH')) {
    // Try to load WordPress configuration
    $wp_config_path = dirname(__FILE__) . '/wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once($wp_config_path);
    } else {
        // If wp-config.php is not found, define constants manually
        // Update these with your actual database credentials
        if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
        if (!defined('DB_USER')) define('DB_USER', 'uihgfite_drill_user');
        if (!defined('DB_PASSWORD')) define('DB_PASSWORD', 'your_password_here');
        if (!defined('DB_NAME')) define('DB_NAME', 'uihgfite_WPUNF');
        if (!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR', dirname(__FILE__) . '/wp-content');
        if (!defined('WP_CONTENT_URL')) define('WP_CONTENT_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content');
    }
}

class DrillAPI {
    private $db;
    private $uploadsDir;
    private $uploadsUrl;
	private $trainingContentDir;
	private $trainingContentUrl;
	private $creditIconsDir;
	private $creditIconsUrl;
    
    public function __construct() {
        try {
            // Use WordPress database connection
            $this->db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            
            if ($this->db->connect_error) {
                throw new Exception('Database connection failed: ' . $this->db->connect_error);
            }
            
            $this->db->set_charset('utf8mb4');
            
            // Set up upload directories
            $this->setupUploadDirectories();
            
            // Create/update diagram table for file-based storage
            $this->createDiagramsTable();

			// Training Content
			$this->createTrainingContentTable();
			$this->setupTrainingContentDirectories();
			$this->addContentIdFieldIfNeeded();
			$this->createTrainingProgramAssignmentsTable();
            
        } catch (Exception $e) {
            error_log("DrillAPI Constructor Error: " . $e->getMessage());
            $this->sendError('API initialization failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Set up upload directories for diagram images
     */
    private function setupUploadDirectories() {
        // Use WordPress uploads directory structure
        if (defined('WP_CONTENT_DIR') && defined('WP_CONTENT_URL')) {
            $wpUploadsDir = WP_CONTENT_DIR . '/uploads';
            $wpUploadsUrl = WP_CONTENT_URL . '/uploads';
        } else {
            // Fallback if WordPress constants are not available
            $wpUploadsDir = dirname(__FILE__) . '/wp-content/uploads';
            $wpUploadsUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content/uploads';
        }
        
        // Create diagram-specific directories
        $this->uploadsDir = $wpUploadsDir . '/diagrams';
        $this->uploadsUrl = $wpUploadsUrl . '/diagrams';
        
        // Create directories if they don't exist
        if (!is_dir($this->uploadsDir)) {
            $this->createDirectory($this->uploadsDir);
        }
        
        // Create thumbnails subdirectory
        $thumbnailsDir = $this->uploadsDir . '/thumbnails';
        if (!is_dir($thumbnailsDir)) {
            $this->createDirectory($thumbnailsDir);
        }
        
        error_log("Setup complete - Uploads Dir: " . $this->uploadsDir . ", URL: " . $this->uploadsUrl);
    }
    
    /**
     * Create directory with proper permissions
     */
    private function createDirectory($dir) {
        if (function_exists('wp_mkdir_p')) {
            return wp_mkdir_p($dir);
        } else {
            // Fallback directory creation
            return mkdir($dir, 0755, true);
        }
    }
    
    /**
     * Create/update diagrams table for file-based storage
     */
    
/**
 * Enhanced createDiagramsTable to include vector data fields
 */
private function createDiagramsTable() {
    $sql = "CREATE TABLE IF NOT EXISTS wp_diagrams (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        diagram_type varchar(50) DEFAULT 'drill',
        visibility enum('public','private') DEFAULT 'private',
        original_filename varchar(255) DEFAULT NULL,
        created_by int(11) DEFAULT NULL,
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        image_url varchar(500) DEFAULT NULL,
        thumbnail_url varchar(500) DEFAULT NULL,
        vector_data LONGTEXT DEFAULT NULL COMMENT 'JSON string containing SVG vector information',
        is_vector tinyint(1) DEFAULT 0 COMMENT '1 if diagram contains vector data, 0 for image files',
        PRIMARY KEY (id),
        KEY idx_diagrams_active (is_active),
        KEY idx_diagrams_name (name),
        KEY fk_diagrams_created_by (created_by),
        KEY idx_diagrams_type (diagram_type),
        KEY idx_diagrams_visibility (visibility),
        KEY idx_diagrams_is_vector (is_vector),
        KEY idx_diagrams_vector_data (vector_data(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    if (!$this->db->query($sql)) {
        error_log("Failed to create wp_diagrams table: " . $this->db->error);
        throw new Exception("Failed to create diagrams table: " . $this->db->error);
    }
    
    // Add vector columns if they don't exist (for existing installations)
    $this->addVectorColumnsIfNeeded();
    
    error_log("Diagrams table created/verified successfully with vector support");
}
    
/**
 * Add vector data columns to existing diagrams table
 */
private function addVectorColumnsIfNeeded() {
    // Check if vector_data column exists
    $result = $this->db->query("SHOW COLUMNS FROM wp_diagrams LIKE 'vector_data'");
    if ($result->num_rows == 0) {
        $this->db->query("ALTER TABLE wp_diagrams ADD COLUMN vector_data LONGTEXT DEFAULT NULL COMMENT 'JSON string containing SVG vector information'");
        $this->db->query("ALTER TABLE wp_diagrams ADD INDEX idx_diagrams_vector_data (vector_data(255))");
        error_log("Added vector_data column to wp_diagrams table");
    }
    
    // Check if is_vector column exists
    $result = $this->db->query("SHOW COLUMNS FROM wp_diagrams LIKE 'is_vector'");
    if ($result->num_rows == 0) {
        $this->db->query("ALTER TABLE wp_diagrams ADD COLUMN is_vector tinyint(1) DEFAULT 0 COMMENT '1 if diagram contains vector data, 0 for image files'");
        $this->db->query("ALTER TABLE wp_diagrams ADD INDEX idx_diagrams_is_vector (is_vector)");
        
        // Update existing records
        $this->db->query("UPDATE wp_diagrams SET is_vector = 0 WHERE vector_data IS NULL OR vector_data = ''");
        $this->db->query("UPDATE wp_diagrams SET is_vector = 1 WHERE vector_data IS NOT NULL AND vector_data != ''");
        
        error_log("Added is_vector column to wp_diagrams table");
    }
}

/**
 * Add content_id field to wp_training_program_content table if it doesn't exist
 */
private function addContentIdFieldIfNeeded() {
    // Check if content_id column exists
    $result = $this->db->query("SHOW COLUMNS FROM wp_training_program_content LIKE 'content_id'");
    if ($result->num_rows == 0) {
        $this->db->query("ALTER TABLE wp_training_program_content ADD COLUMN content_id int DEFAULT NULL AFTER drill_id");
        $this->db->query("ALTER TABLE wp_training_program_content ADD INDEX idx_training_content_id (content_id)");
        error_log("Added content_id column to wp_training_program_content table");
    }
}

    /**
     * Generate thumbnail filename using diagram ID
     */
    private function generateThumbnailFilenameFromId($diagramId, $extension) {
        return 'thumb_diagram_' . $diagramId . '.' . $extension;
    }
    
    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType($mimeType) {
        $mimeToExt = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        );
        
        return isset($mimeToExt[$mimeType]) ? $mimeToExt[$mimeType] : 'jpg';
    }
    
    /**
     * Generate thumbnail at specific path
     */

private function generateThumbnailAtPath($sourcePath, $thumbnailPath, $mimeType) {
    // Check if GD extension is available
    if (!extension_loaded('gd')) {
        error_log("GD extension not available for thumbnail generation");
        return false;
    }
    
    try {
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        
        // Calculate thumbnail dimensions
        $maxSize = 300;
        if ($sourceWidth > $sourceHeight) {
            $thumbWidth = $maxSize;
            $thumbHeight = intval(($sourceHeight * $maxSize) / $sourceWidth);
        } else {
            $thumbHeight = $maxSize;
            $thumbWidth = intval(($sourceWidth * $maxSize) / $sourceHeight);
        }
        
        // Create source image
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create thumbnail image
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType == 'image/png' || $mimeType == 'image/gif') {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
            $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
            imagefilledrectangle($thumbImage, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }
        
        // Resize image
        imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight);
        
        // Save thumbnail
        $success = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $success = imagejpeg($thumbImage, $thumbnailPath, 85);
                break;
            case 'image/png':
                $success = imagepng($thumbImage, $thumbnailPath, 8);
                break;
            case 'image/gif':
                $success = imagegif($thumbImage, $thumbnailPath);
                break;
        }
        
        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($thumbImage);
        
        return $success;
        
    } catch (Exception $e) {
        error_log("Thumbnail generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * MISSING METHODS FOR DIAGRAM MANAGEMENT
 * Add these methods to your DrillAPI class
 */

/**
 * Generate filename using diagram ID (primary key)
 */
private function generateFilenameFromId($diagramId, $extension) {
    return 'diagram_' . $diagramId . '.' . $extension;
}

    
    /**
     * Parse multipart form data for PUT requests
     */
    private function parseMultipartFormData(&$fields, &$files) {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            error_log("No input data for multipart parsing");
            return;
        }
        
        // Get the boundary
        $boundary = null;
        if (preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches)) {
            $boundary = $matches[1];
        }
        
        if (!$boundary) {
            error_log("No boundary found in Content-Type");
            return;
        }
        
        error_log("Parsing multipart data with boundary: $boundary");
        
        // Split the input by boundary
        $parts = array_slice(explode('--' . $boundary, $input), 1);
        
        foreach ($parts as $part) {
            if (trim($part) === '--' || empty(trim($part))) {
                continue;
            }
            
            // Split headers and content
            $headerContentSplit = explode("\r\n\r\n", $part, 2);
            if (count($headerContentSplit) < 2) {
                continue;
            }
            
            $rawHeaders = $headerContentSplit[0];
            $content = rtrim($headerContentSplit[1], "\r\n");
            
            // Parse headers
            $headers = array();
            foreach (explode("\r\n", $rawHeaders) as $header) {
                if (strpos($header, ':') !== false) {
                    $headerParts = explode(':', $header, 2);
                    $headers[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
                }
            }
            
            // Parse Content-Disposition
            if (isset($headers['content-disposition'])) {
                if (preg_match('/name="([^"]*)"/', $headers['content-disposition'], $nameMatch)) {
                    $fieldName = $nameMatch[1];
                    
                    if (preg_match('/filename="([^"]*)"/', $headers['content-disposition'], $filenameMatch)) {
                        // This is a file upload
                        $filename = $filenameMatch[1];
                        $tempFile = tempnam(sys_get_temp_dir(), 'upload');
                        file_put_contents($tempFile, $content);
                        
                        $files[$fieldName] = array(
                            'name' => $filename,
                            'type' => isset($headers['content-type']) ? $headers['content-type'] : 'application/octet-stream',
                            'tmp_name' => $tempFile,
                            'error' => UPLOAD_ERR_OK,
                            'size' => strlen($content)
                        );
                        
                        error_log("Parsed file field '$fieldName': $filename (" . strlen($content) . " bytes)");
                    } else {
                        // This is a regular field
                        $fields[$fieldName] = $content;
                        error_log("Parsed field '$fieldName': $content");
                    }
                }
            }
        }
    }
    
    /**
     * Main request handler
     */
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Remove script name from path
            $scriptName = basename($_SERVER['SCRIPT_NAME']);
            $path = str_replace($scriptName, '', $path);
            $path = trim($path, '/');
            
            // Parse endpoint and ID
			$segments = array_filter(explode('/', $path));
			$segments = array_values($segments);

			// Handle compound endpoints
			$endpoint = isset($segments[0]) ? $segments[0] : '';
			$id = null;

			// Check if we have a second segment that looks like an action (not numeric ID)
			if (count($segments) >= 2 && !is_numeric($segments[1])) {
				$endpoint = $segments[0] . '/' . $segments[1];
				$id = isset($segments[2]) ? $segments[2] : null;
			} elseif (count($segments) >= 2) {
				$id = $segments[1];
			}
            
            error_log("API Request: Method=$method, Endpoint=$endpoint, ID=$id");
            
            // Get request data
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Handle URL-encoded data for PUT requests
            if ($method === 'PUT' && empty($input)) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            // Handle _method parameter for hosting providers that don't support DELETE
            if ($method === 'POST' && isset($input['_method'])) {
                $method = strtoupper($input['_method']);
            }
            
            switch ($endpoint) {
                case 'login':
                    if ($method === 'POST') {
                        $this->login($input);
                    }
                    break;
                    
                case 'admin-login':
                    if ($method === 'POST') {
                        $this->adminLogin($input);
                    }
                    break;
                    
                case 'users':
                    if ($method === 'GET' && $id) {
                        $this->getUser($id);
                    } elseif ($method === 'GET') {
                        $this->getUsers();
                    } elseif ($method === 'POST') {
                        $this->createUser($input);
                    }
                    break;
                    
                case 'categories':
                    if ($method === 'GET') {
                        $this->getCategories();
                    }
                    break;
                    
                case 'skills':
                    if ($method === 'GET') {
                        $this->getSkills();
                    }
                    break;
                    
                case 'drills':
                    if ($method === 'GET' && $id) {
                        $this->getDrill($id);
                    } elseif ($method === 'GET') {
                        $this->getDrills($_GET);
                    } elseif ($method === 'POST' && !$id) {
                        $this->createDrill($input);
                    } elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
                        // Handle delete via POST
                        $this->deleteDrill($id);
                    } elseif ($method === 'PUT' && $id) {
                        $this->updateDrill($id, $input);
                    } elseif ($method === 'DELETE' && $id) {
                        $this->deleteDrill($id);
                    }
                    break;
				case 'toggle-unit-lock':
					if ($method === 'POST' || $method === 'PATCH') {
						$this->toggleUnitLock($input);
					} else {
						$this->sendError('Only POST and PATCH methods allowed for toggle-unit-lock', 405);
					}
					break;                    
                case 'assignments':
                    if ($method === 'GET' && $id) {
                        $this->getAssignment($id);
                    } elseif ($method === 'GET') {
                        $this->getAssignments($_GET);
                    } elseif ($method === 'POST' && !$id) {
                        $this->createAssignment($input);
                    } elseif ($method === 'PUT' && $id) {
                        $this->updateAssignment($id, $input);
                    } elseif ($method === 'DELETE' && $id) {
                        $this->deleteAssignment($id);
                    } elseif ($method === 'POST' && $id && isset($input['_method']) && $input['_method'] === 'DELETE') {
                        // Handle delete via POST for hosting compatibility
                        $this->deleteAssignment($id);
                    } elseif ($method === 'POST' && $id && isset($input['is_active'])) {
                        // Handle soft delete via POST
                        $this->updateAssignment($id, $input);
                    }
                    break;
                    
				case 'content-assignments':
					if ($method === 'GET' && $id) {
						$this->getContentAssignment($id);
					} elseif ($method === 'GET') {
						$this->getContentAssignments($_GET);
					} elseif ($method === 'POST' && !$id) {
						$this->createContentAssignment($input);
					} elseif ($method === 'PUT' && $id) {
						$this->updateContentAssignment($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteContentAssignment($id);
					} elseif ($method === 'POST' && $id && isset($input['_method']) && $input['_method'] === 'DELETE') {
						$this->deleteContentAssignment($id);
					} elseif ($method === 'POST' && $id && isset($input['is_active'])) {
						$this->updateContentAssignment($id, $input);
					}
					break;

                case 'scores':
                    if ($method === 'GET') {
                        $this->getScores($_GET);
                    } elseif ($method === 'POST') {
                        $this->submitScore($input);
                    }
                    break;
                    
                case 'stats':
                    if ($method === 'GET') {
                        $this->getStats($_GET);
                    }
                    break;

				case 'drill-stats':
					if ($method === 'GET') {
						$this->getDrillStats($_GET);
					}
					break;
                    
                case 'journal':
                    if ($method === 'GET' && $id) {
                        $this->getJournalEntry($id);
                    } elseif ($method === 'GET') {
                        $this->getJournalEntries($_GET);
                    } elseif ($method === 'POST' && !$id) {
                        $this->createJournalEntry($input);
                    } elseif ($method === 'PUT' && $id) {
                        $this->updateJournalEntry($id, $input);
                    } elseif ($method === 'DELETE' && $id) {
                        $this->deleteJournalEntry($id);
                    } elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
                        // Handle delete via POST for hosting compatibility
                        $this->deleteJournalEntry($id);
                    }
                    break;
                    
                case 'challenge-events':
                    if ($method === 'GET' && $id) {
                        $this->getChallengeEvent($id);
                    } elseif ($method === 'GET') {
                        $this->getChallengeEvents($_GET);
                    } elseif ($method === 'POST' && !$id) {
                        $this->createChallengeEvent($input);
                    } elseif ($method === 'PUT' && $id) {
                        $this->updateChallengeEvent($id, $input);
                    } elseif ($method === 'DELETE' && $id) {
                        $this->deleteChallengeEvent($id);
                    } elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
                        // Handle delete via POST for hosting compatibility
                        $this->deleteChallengeEvent($id);
                    }
                    break;
                    
                case 'challenge-scoring-methods':
                    if ($method === 'GET') {
                        $this->getChallengeScoringMethods();
                    }
                    break;
                    
                case 'challenge-participants':
                    if ($method === 'GET') {
                        $this->getChallengeParticipants($_GET);
                    } elseif ($method === 'POST') {
                        $this->addChallengeParticipant($input);
                    } elseif ($method === 'DELETE' && $id) {
                        $this->removeChallengeParticipant($id);
                    }
                    break;
                    
                case 'challenge-scores':
                    if ($method === 'GET') {
                        $this->getChallengeScores($_GET);
                    } elseif ($method === 'POST') {
                        $this->submitChallengeScore($input);
                    }
                    break;
                    
                case 'diagrams':
                    if ($method === 'GET' && $id) {
                        $this->getDiagram($id);
                    } elseif ($method === 'GET') {
                        $this->getDiagrams($_GET);
                    } elseif ($method === 'POST' && !$id) {
                        $this->createDiagram();
                    } elseif ($method === 'PUT' && $id) {
                        $this->updateDiagram($id);
                    } elseif ($method === 'DELETE' && $id) {
                        $this->deleteDiagram($id);
                    } else {
                        $this->sendError('Invalid diagrams request', 400);
                    }
                    break;

				case 'coach-login':
					if ($method === 'POST') {
						$this->coachLogin($input);
					}
					break;

				case 'coach-students':
					if ($method === 'GET') {
						$this->getCoachStudents($_GET);
					}
					break;

				case 'coach-scores':
					if ($method === 'GET') {
						$this->getCoachScores($_GET);
					}
					break;

				case 'coach-assignments':
					if ($method === 'GET') {
						$this->getCoachAssignments($_GET);
					}
					break;

				case 'coach-activity':
					if ($method === 'GET') {
						$this->getCoachActivity($_GET);
					}
					break;
				case 'training-programs':
					if ($method === 'GET' && $id) {
						$this->getTrainingProgram($id);
					} elseif ($method === 'GET') {
						$this->getTrainingPrograms($_GET);
					} elseif ($method === 'POST' && !$id) {
						$this->createTrainingProgram($input);
					} elseif ($method === 'PUT' && $id) {
						$this->updateTrainingProgram($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteTrainingProgram($id);
					} elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
						// Handle delete via POST for hosting compatibility
						$this->deleteTrainingProgram($id);
					}
					break;

				case 'training-program-units':
					if ($method === 'GET' && $id) {
						$this->getTrainingProgramUnit($id);
					} elseif ($method === 'GET') {
						$this->getTrainingProgramUnits($_GET);
					} elseif ($method === 'POST' && !$id) {
						$this->createTrainingProgramUnit($input);
					} elseif ($method === 'PUT' && $id) {
						$this->updateTrainingProgramUnit($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteTrainingProgramUnit($id);
					} elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
						// Handle delete via POST for hosting compatibility
						$this->deleteTrainingProgramUnit($id);
					}
					break;                    
				case 'training-program-assignments/next-sequence':
					if ($method === 'GET') {
						$this->getNextAssignmentSequence($_GET);
					} else {
						$this->sendError('Only GET method allowed for next-sequence endpoint', 405);
					}
					break;
					
				case 'training-program-assignments/history':
					if ($method === 'GET') {
						$this->getAssignmentHistory($_GET);
					} else {
						$this->sendError('Only GET method allowed for history endpoint', 405);
					}
					break;
				case 'training-content':
					if ($method === 'GET' && $id) {
						$this->getTrainingContent($id);
					} elseif ($method === 'GET') {
						$this->getTrainingContents($_GET);
					} elseif ($method === 'POST' && !$id) {
						$this->createTrainingContent();
					} elseif ($method === 'PUT' && $id) {
						$this->updateTrainingContent($id);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteTrainingContent($id);
					} else {
						$this->sendError('Invalid training content request', 400);
					}
					break;

                case 'version':
                    if ($method === 'GET') {
                        $this->getVersion();
                    }
                    break;
                    
                case 'debug-blob':
                    if ($method === 'GET') {
                        $this->debugFileStorage();
                    }
                    break;
				case 'training-program-content':
					if ($method === 'GET' && $id) {
						$this->getTrainingProgramContent($id);
					} elseif ($method === 'GET') {
						$this->getTrainingProgramContents($_GET);
					} elseif ($method === 'POST' && !$id) {
						$this->createTrainingProgramContent($input);
					} elseif ($method === 'PUT' && $id) {
						$this->updateTrainingProgramContent($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteTrainingProgramContent($id);
					} elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
						// Handle delete via POST for hosting compatibility
						$this->deleteTrainingProgramContent($id);
					}
					break;

				case 'training-program-content-batch':
					if ($method === 'POST') {
						$this->createBatchTrainingProgramContent($input);
					}
					break;

				case 'unit-content':
					if ($method === 'GET') {
						$this->getUnitContent($_GET);
					}
					break;

				case 'coach-student-assignments':
					if ($method === 'POST') {
						$this->manageCoachStudentAssignments($input);
					} elseif ($method === 'GET') {
						$this->getCoachStudentAssignments($_GET);
					}
					break;
				case 'training-program-assignments':
					if ($method === 'GET' && $id) {
						$this->getTrainingProgramAssignment($id);
					} elseif ($method === 'GET') {
						$this->getTrainingProgramAssignments($_GET);
					} elseif ($method === 'POST' && !$id) {
						// UPDATED: This will now handle assignment sequences automatically
						$this->createTrainingProgramAssignment($input);
					} elseif ($method === 'PUT' && $id) {
						$this->updateTrainingProgramAssignment($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteTrainingProgramAssignment($id);
					} elseif ($method === 'PATCH' && $id) {
						// NEW: Handle soft updates like status changes
						$this->updateTrainingProgramAssignment($id, $input);
					} elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
						// Handle delete via POST for hosting compatibility
						$this->deleteTrainingProgramAssignment($id);
					}
					break;
				case 'training-program-units-assigned':
					if ($method === 'GET') {
						$this->getTrainingProgramUnitsAssigned($_GET);
					} else {
						$this->sendError('Only GET method allowed for units-assigned endpoint', 405);
					}
					break;

				case 'training-program-content-assigned':
					if ($method === 'GET') {
						$this->getTrainingProgramContentAssigned($_GET);
					} else {
						$this->sendError('Only GET method allowed for content-assigned endpoint', 405);
					}
					break;
				case 'credits':
					if ($method === 'GET' && $id) {
						$this->getCredit($id);
					} elseif ($method === 'GET') {
						$this->getCredits($_GET);
					
					} elseif ($method === 'POST' && !$id) {
						// Check if this is actually an edit operation disguised as POST (for file uploads)
						if (isset($_POST['_method']) && $_POST['_method'] === 'PUT' && isset($_POST['credit_id'])) {
							// This is an edit operation with file upload
							$creditId = intval($_POST['credit_id']);
							$this->updateCredit($creditId, $input);
						} else {
							// This is a regular create operation
							$this->createCredit($input);
						}
					} elseif ($method === 'PUT' && $id) {
						$this->updateCredit($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteCredit($id);
					} elseif ($method === 'POST' && $id && isset($input['action']) && $input['action'] === 'delete') {
						$this->deleteCredit($id);
					} else {
						$this->sendError('Invalid credits request', 400);
					}
					break;										
				case 'debug-credits':
					if ($method === 'GET') {
						$this->debugCredits();
					}
					break;

				case 'achievement-schemes':
					if ($method === 'GET') {
						$this->getAchievementSchemes();
					}
					break;

				case 'user-achievement-level':
					if ($method === 'POST') {
						$this->calculateUserAchievementLevel(
							$input['user_id'] ?? 0,
							$input['drill_id'] ?? 0,
							$input['user_score'] ?? null
						);
					} else {
						$this->sendError('POST method required', 405);
					}
					break;
					
				case 'achievement-types':
					if ($method === 'GET' && $id) {
						$this->getAchievementType($id);
					} elseif ($method === 'GET') {
						$this->getAchievementTypes();
					} elseif ($method === 'POST' && !$id) {
						$this->createAchievementType($input);
					} elseif ($method === 'PUT' && $id) {
						$this->updateAchievementType($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteAchievementType($id);
					} else {
						$this->sendError('Invalid achievement-types request', 400);
					}
					break;

				case 'achievement-levels':
					if ($method === 'GET' && $id) {
						$this->getAchievementLevels($id);
					} elseif ($method === 'POST' && !$id) {
						$this->createAchievementLevel($input);
					} elseif ($method === 'PUT' && $id) {
						$this->updateAchievementLevel($id, $input);
					} elseif ($method === 'DELETE' && $id) {
						$this->deleteAchievementLevel($id);
					} else {
						$this->sendError('Invalid achievement-levels request', 400);
					}
					break;					
					
                default:
                    $this->sendError("Endpoint '$endpoint' not found. Available endpoints: login, admin-login, users, categories, skills, drills, assignments, scores, stats, journal, challenge-events, challenge-scoring-methods, challenge-participants, challenge-scores, diagrams, version, debug-blob", 404);
            }
            
        } catch (Exception $e) {
            error_log("Request Handler Error: " . $e->getMessage());
            $this->sendError('Request processing failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * USER AUTHENTICATION
     */
    public function login($data) {
        $email = strtolower(trim($data['email'] ?? ''));
        
        if (empty($email)) {
            $this->sendError('Email is required', 400);
        }
        
        $stmt = $this->db->prepare("SELECT * FROM wp_drill_users WHERE email = ? AND is_active = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $this->sendSuccess([
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['display_name'],
                    'type' => $user['user_type']
                ],
                'token' => base64_encode($user['id'] . ':' . time()) // Simple token for demo
            ]);
        } else {
            $this->sendError('Email not authorized', 401);
        }
    }
    
    /**
     * ADMIN AUTHENTICATION
     */
    public function adminLogin($data = null) {
        // Handle both input methods
        if ($data === null) {
            // Simple success response for testing (from current version)
            $this->sendSuccess(array(
                'admin' => array(
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@test.com',
                    'type' => 'admin'
                ),
                'token' => base64_encode('1:admin:' . time()),
                'message' => 'Admin login successful'
            ));
            return;
        }
        
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        
        if (empty($username) || empty($password)) {
            $this->sendError('Username and password are required', 400);
        }
        
        // Check admin credentials in database
        $stmt = $this->db->prepare("
            SELECT * FROM wp_drill_users 
            WHERE (email = ? OR display_name = ?) 
            AND user_type = 'admin' 
            AND is_active = 1
        ");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($admin = $result->fetch_assoc()) {
            // For now, check if password matches a simple stored value
            // In production, you'd want proper password hashing
            $stored_password = $this->getAdminPassword($admin['id']);
            
            if ($this->verifyPassword($password, $stored_password)) {
                // Update last login time
                $this->updateLastLogin($admin['id']);
                
                $this->sendSuccess([
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['display_name'],
                        'email' => $admin['email'],
                        'type' => $admin['user_type']
                    ],
                    'token' => base64_encode($admin['id'] . ':admin:' . time()),
                    'message' => 'Admin login successful'
                ]);
            } else {
                $this->sendError('Invalid password', 401);
            }
        } else {
            $this->sendError('Invalid username or unauthorized access', 401);
        }
    }
    
    private function getAdminPassword($adminId) {
        // Check if admin_passwords table exists, if not create it
        $this->createAdminPasswordsTable();
        
        $stmt = $this->db->prepare("SELECT password FROM wp_admin_passwords WHERE user_id = ?");
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['password'];
        }
        
        // Default password for admin users if none set
        return 'admin';
    }
    
    private function verifyPassword($inputPassword, $storedPassword) {
        // Simple comparison for now - in production use password_verify()
        return $inputPassword === $storedPassword;
    }
    
    private function updateLastLogin($adminId) {
        $stmt = $this->db->prepare("UPDATE wp_drill_users SET updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
    }
    
    private function createAdminPasswordsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS wp_admin_passwords (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            password varchar(255) NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES wp_drill_users(id) ON DELETE CASCADE
        )";
        
        $this->db->query($sql);
        
        // Insert default admin password if table was just created
        $checkStmt = $this->db->prepare("SELECT COUNT(*) as count FROM wp_admin_passwords");
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count == 0) {
            // Insert default password for all admin users
            $adminStmt = $this->db->prepare("SELECT id FROM wp_drill_users WHERE user_type = 'admin'");
            $adminStmt->execute();
            $admins = $adminStmt->get_result();
            
            while ($admin = $admins->fetch_assoc()) {
                $insertStmt = $this->db->prepare("INSERT INTO wp_admin_passwords (user_id, password) VALUES (?, 'admin')");
                $insertStmt->bind_param('i', $admin['id']);
                $insertStmt->execute();
            }
        }
    }
    
    /**
     * USER MANAGEMENT
     */
    public function getUsers() {
        $sql = "SELECT id, email, display_name, user_type, is_active, created_at, coach_id 
                FROM wp_drill_users 
                WHERE is_active = 1 
                ORDER BY display_name";
        
        $result = $this->db->query($sql);
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        $this->sendSuccess($users);
    }
    
    public function getUser($id) {
        $stmt = $this->db->prepare("SELECT * FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $this->sendSuccess($user);
        } else {
            $this->sendError('User not found', 404);
        }
    }
    
    public function createUser($data) {
        $email = strtolower(trim($data['email'] ?? ''));
        $name = trim($data['name'] ?? '');
        $type = $data['type'] ?? 'player';
        
        if (empty($email) || empty($name)) {
            $this->sendError('Email and name are required', 400);
        }
        
        $stmt = $this->db->prepare("INSERT INTO wp_drill_users (email, display_name, user_type) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $email, $name, $type);
        
        if ($stmt->execute()) {
            $this->sendSuccess(['id' => $this->db->insert_id, 'message' => 'User created successfully']);
        } else {
            $this->sendError('Failed to create user', 500);
        }
    }

	/**
	 * COACH AUTHENTICATION AND MANAGEMENT
	 */

	/**
	 * Coach login with password authentication
	 */
	public function coachLogin($data) {
		$email = strtolower(trim($data['email'] ?? ''));
		$password = trim($data['password'] ?? '');
		
		if (empty($email) || empty($password)) {
			$this->sendError('Email and password are required', 400);
		}
		
		// Check if user exists and is a coach
		$stmt = $this->db->prepare("SELECT * FROM wp_drill_users WHERE email = ? AND user_type = 'coach' AND is_active = 1");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($coach = $result->fetch_assoc()) {
			// Get password from separate table (you'll need to create this)
			$passwordCheck = $this->verifyCoachPassword($coach['id'], $password);
			
			if ($passwordCheck) {
				// Update last login
				$this->updateLastLogin($coach['id']);
				
				$this->sendSuccess([
					'coach' => [
						'id' => $coach['id'],
						'email' => $coach['email'],
						'name' => $coach['display_name'],
						'type' => 'coach',
						'role' => 'coach'
					],
					'token' => base64_encode($coach['id'] . ':coach:' . time())
				]);
			} else {
				$this->sendError('Invalid credentials', 401);
			}
		} else {
			$this->sendError('Invalid credentials or not authorized as coach', 401);
		}
	}

	private function verifyCoachPassword($coachId, $password) {
		// Create coach passwords table if it doesn't exist
		$this->createCoachPasswordsTable();
		
		$stmt = $this->db->prepare("SELECT password_hash FROM wp_coach_passwords WHERE coach_id = ?");
		$stmt->bind_param('i', $coachId);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($row = $result->fetch_assoc()) {
			// Use password_verify for hashed passwords, or simple comparison for plain text
			return password_verify($password, $row['password_hash']) || $password === $row['password_hash'];
		}
		
		// Default password if none set
		return $password === 'coach123';
	}

	private function createCoachPasswordsTable() {
		$sql = "CREATE TABLE IF NOT EXISTS wp_coach_passwords (
			id INT NOT NULL AUTO_INCREMENT,
			coach_id INT NOT NULL,
			password_hash VARCHAR(255) NOT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY coach_id (coach_id),
			FOREIGN KEY (coach_id) REFERENCES wp_drill_users(id) ON DELETE CASCADE
		)";
		
		$this->db->query($sql);
	}

	/**
	 * Get students assigned to a coach
	 */
	
	public function getCoachStudents($params = []) {
		$coach_id = $params['coach_id'] ?? 0;
		
		if (!$coach_id) {
			$this->sendError('Coach ID is required', 400);
		}
		
		// You'll need to add a coach assignment table or add coach_id to wp_drill_users
		$sql = "SELECT u.*, 
					   COUNT(s.id) as total_scores,
					   AVG(s.percentage) as avg_percentage,
					   COUNT(a.id) as active_assignments
				FROM wp_drill_users u
				LEFT JOIN wp_drill_scores s ON u.id = s.user_id
				LEFT JOIN wp_drill_assignments a ON u.id = a.user_id AND a.is_active = 1
				WHERE u.coach_id = ? AND u.is_active = 1 AND u.user_type IN ('student', 'player')
				GROUP BY u.id
				ORDER BY u.display_name";
		
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('i', $coach_id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		$students = [];
		while ($row = $result->fetch_assoc()) {
			$students[] = $row;
		}
		
		$this->sendSuccess($students);
	}

	/**
	 * Get scores for all students of a coach
	 */
	public function getCoachScores($params = []) {
		$coach_id = $params['coach_id'] ?? 0;
		$limit = isset($params['limit']) ? (int)$params['limit'] : 50;
		
		if (!$coach_id) {
			$this->sendError('Coach ID is required', 400);
		}
		
		$sql = "SELECT s.*, d.name as drill_name, u.display_name as student_name
				FROM wp_drill_scores s
				JOIN wp_drills d ON s.drill_id = d.id
				JOIN wp_drill_users u ON s.user_id = u.id
				WHERE u.coach_id = ?
				ORDER BY s.submitted_at DESC
				LIMIT ?";
		
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('ii', $coach_id, $limit);
		$stmt->execute();
		$result = $stmt->get_result();
		
		$scores = [];
		while ($row = $result->fetch_assoc()) {
			$scores[] = $row;
		}
		
		$this->sendSuccess($scores);
	}

	/**
	 * Get assignments created by a coach
	 */
	public function getCoachAssignments($params = []) {
		$coach_id = $params['coach_id'] ?? 0;
		
		if (!$coach_id) {
			$this->sendError('Coach ID is required', 400);
		}
		
		$sql = "SELECT a.*, d.name as drill_name, u.display_name as student_name
				FROM wp_drill_assignments a
				JOIN wp_drills d ON a.drill_id = d.id
				JOIN wp_drill_users u ON a.user_id = u.id
				WHERE a.assigned_by = ? AND a.is_active = 1
				ORDER BY a.assigned_date DESC";
		
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('i', $coach_id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		$assignments = [];
		while ($row = $result->fetch_assoc()) {
			$assignments[] = $row;
		}
		
		$this->sendSuccess($assignments);
	}

	/**
	 * Get recent activity for coach's students
	 */
	public function getCoachActivity($params = []) {
		$coach_id = $params['coach_id'] ?? 0;
		$limit = isset($params['limit']) ? (int)$params['limit'] : 10;
		
		if (!$coach_id) {
			$this->sendError('Coach ID is required', 400);
		}
		
		$sql = "SELECT s.submitted_at as timestamp, 
					   CONCAT(u.display_name, ' submitted score for ', d.name) as description,
					   'score_submitted' as type
				FROM wp_drill_scores s
				JOIN wp_drill_users u ON s.user_id = u.id
				JOIN wp_drills d ON s.drill_id = d.id
				WHERE u.coach_id = ?
				ORDER BY s.submitted_at DESC
				LIMIT ?";
		
		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('ii', $coach_id, $limit);
		$stmt->execute();
		$result = $stmt->get_result();
		
		$activities = [];
		while ($row = $result->fetch_assoc()) {
			$activities[] = $row;
		}
		
		$this->sendSuccess($activities);
	}
/**
 * Manage coach-student assignments (assign or remove students from coaches)
 */
public function manageCoachStudentAssignments($data) {
    try {
        $coach_id = $data['coach_id'] ?? null;
        $student_ids = $data['student_ids'] ?? [];
        
        if (!is_array($student_ids) || empty($student_ids)) {
            $this->sendError('Student IDs array is required', 400);
            return;
        }
        
        // Validate coach exists if coach_id is provided
        if ($coach_id !== null) {
            $coachCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND user_type = 'coach' AND is_active = 1");
            $coachCheck->bind_param('i', $coach_id);
            $coachCheck->execute();
            if ($coachCheck->get_result()->num_rows === 0) {
                $this->sendError('Invalid coach specified', 400);
                return;
            }
        }
        
        // Validate all student IDs exist
        $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
        $studentCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id IN ($placeholders) AND user_type = 'player' AND is_active = 1");
        $studentCheck->bind_param(str_repeat('i', count($student_ids)), ...$student_ids);
        $studentCheck->execute();
        $validStudents = $studentCheck->get_result();
        
        if ($validStudents->num_rows !== count($student_ids)) {
            $this->sendError('One or more invalid student IDs provided', 400);
            return;
        }
        
        // Start transaction
        $this->db->begin_transaction();
        
        try {
            $updated_count = 0;
            
            // Update each student's coach_id
            $updateStmt = $this->db->prepare("UPDATE wp_drill_users SET coach_id = ? WHERE id = ? AND user_type = 'player'");
            
            foreach ($student_ids as $student_id) {
                $updateStmt->bind_param('ii', $coach_id, $student_id);
                if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                    $updated_count++;
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            if ($coach_id === null) {
                $message = "Successfully removed $updated_count students from coach assignments";
            } else {
                $coachName = $this->getCoachName($coach_id);
                $message = "Successfully assigned $updated_count students to coach $coachName";
            }
            
            $this->sendSuccess([
                'message' => $message,
                'updated_count' => $updated_count,
                'coach_id' => $coach_id,
                'student_ids' => $student_ids
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("manageCoachStudentAssignments error: " . $e->getMessage());
        $this->sendError('Failed to manage coach-student assignments: ' . $e->getMessage(), 500);
    }
}

/**
 * Get coach-student assignment information
 */
public function getCoachStudentAssignments($params = []) {
    try {
        $coach_id = $params['coach_id'] ?? null;
        
        if ($coach_id) {
            // Get students for a specific coach
            $stmt = $this->db->prepare("
                SELECT u.*, 'assigned' as assignment_status
                FROM wp_drill_users u
                WHERE u.coach_id = ? AND u.user_type = 'player' AND u.is_active = 1
                ORDER BY u.display_name
            ");
            $stmt->bind_param('i', $coach_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            
            $this->sendSuccess($students);
            
        } else {
            // Get all assignment information
            $sql = "
                SELECT 
                    c.id as coach_id,
                    c.display_name as coach_name,
                    c.email as coach_email,
                    COUNT(s.id) as student_count,
                    GROUP_CONCAT(s.display_name ORDER BY s.display_name SEPARATOR ', ') as student_names
                FROM wp_drill_users c
                LEFT JOIN wp_drill_users s ON c.id = s.coach_id AND s.user_type = 'player' AND s.is_active = 1
                WHERE c.user_type = 'coach' AND c.is_active = 1
                GROUP BY c.id, c.display_name, c.email
                ORDER BY c.display_name
            ";
            
            $result = $this->db->query($sql);
            $assignments = [];
            
            while ($row = $result->fetch_assoc()) {
                $assignments[] = $row;
            }
            
            $this->sendSuccess($assignments);
        }
        
    } catch (Exception $e) {
        error_log("getCoachStudentAssignments error: " . $e->getMessage());
        $this->sendError('Failed to get coach-student assignments: ' . $e->getMessage(), 500);
    }
}

/**
 * Helper method to get coach name
 */
private function getCoachName($coach_id) {
    $stmt = $this->db->prepare("SELECT display_name FROM wp_drill_users WHERE id = ?");
    $stmt->bind_param('i', $coach_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['display_name'];
    }
    
    return 'Unknown Coach';
}

    
    /**
     * DRILL CATEGORIES
     */
    public function getCategories() {
        $sql = "SELECT * FROM wp_drill_categories WHERE is_active = 1 ORDER BY sort_order";
        $result = $this->db->query($sql);
        $categories = [];
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $this->sendSuccess($categories);
    }
    
    /**
     * DRILL SKILLS
     */
    public function getSkills() {
        $sql = "SELECT * FROM wp_drill_skills WHERE is_active = 1 ORDER BY sort_order";
        $result = $this->db->query($sql);
        $skills = [];
        
        while ($row = $result->fetch_assoc()) {
            $skills[] = $row;
        }
        
        $this->sendSuccess($skills);
    }
    
    /**
     * DRILLS
     */

/**
 * ENHANCED getDrills with achievement type support
 */
public function getDrills($params = []) {
    $where = ["d.is_active = 1"];
    $bindings = [];
    $types = "";
    
    // Filter by category
    if (!empty($params['category_id'])) {
        $where[] = "d.category_id = ?";
        $bindings[] = $params['category_id'];
        $types .= "i";
    }
    
    // Filter by skill
    if (!empty($params['skill_id'])) {
        $where[] = "d.skill_id = ?";
        $bindings[] = $params['skill_id'];
        $types .= "i";
    }
    
    // Filter by achievement type
    if (!empty($params['achievement_type_id'])) {
        $where[] = "d.achievement_type_id = ?";
        $bindings[] = $params['achievement_type_id'];
        $types .= "i";
    }
    
    // Filter by user assignments
    if (!empty($params['user_id']) && !empty($params['assigned_only'])) {
        $where[] = "EXISTS (SELECT 1 FROM wp_drill_assignments da WHERE da.drill_id = d.id AND da.user_id = ? AND da.is_active = 1)";
        $bindings[] = $params['user_id'];
        $types .= "i";
    }
    
    // ENHANCED SQL: Added achievement type information
    $sql = "SELECT d.*, 
                   dc.name as category_name, dc.display_name as category_display,
                   ds.name as skill_name, ds.display_name as skill_display,
                   
                   -- Credit information
                   cr.organization_name as credit_organization_name,
                   cr.website_url as credit_website_url,
                   cr.icon_url as credit_icon_url,
                   cr.description as credit_description,
                   
                   -- Achievement type information
                   at.name as achievement_type_name,
                   at.description as achievement_type_description,
                   at.calculation_method as achievement_calculation_method,
                   (SELECT COUNT(*) FROM wp_achievement_levels al 
                    WHERE al.achievement_type_id = at.id) as achievement_level_count
                   
            FROM wp_drills d
            JOIN wp_drill_categories dc ON d.category_id = dc.id
            JOIN wp_drill_skills ds ON d.skill_id = ds.id
            LEFT JOIN wp_credit_to cr ON d.credit_id = cr.id AND cr.is_active = 1
            LEFT JOIN wp_achievement_types at ON d.achievement_type_id = at.id AND at.is_active = 1
            WHERE " . implode(' AND ', $where) . "
            ORDER BY dc.sort_order, ds.sort_order, d.name";
    
    if (!empty($bindings)) {
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$bindings);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $this->db->query($sql);
    }
    
    $drills = [];
    while ($row = $result->fetch_assoc()) {
        // Log achievement information for debugging
        if ($row['achievement_type_id']) {
            error_log("Drill '{$row['name']}' has achievement_type_id: {$row['achievement_type_id']}, type: {$row['achievement_type_name']}");
        }
        
        $drills[] = $row;
    }
    
    error_log("getDrills returning " . count($drills) . " drills with achievement type info");
    $this->sendSuccess($drills);
}

    
/**
 * ENHANCED getDrill with achievement type support
 */
public function getDrill($id) {
    $stmt = $this->db->prepare("
        SELECT d.*, 
               dc.display_name as category_display,
               ds.display_name as skill_display,
               diag.name as diagram_name,
               diag.image_url as diagram_image_url,
               diag.thumbnail_url as diagram_thumbnail_url,
               
               -- Credit information
               cr.organization_name as credit_organization_name,
               cr.website_url as credit_website_url,
               cr.icon_url as credit_icon_url,
               cr.description as credit_description,
               
               -- Achievement type information
               at.name as achievement_type_name,
               at.description as achievement_type_description,
               at.calculation_method as achievement_calculation_method,
               (SELECT COUNT(*) FROM wp_achievement_levels al 
                WHERE al.achievement_type_id = at.id) as achievement_level_count
               
        FROM wp_drills d
        JOIN wp_drill_categories dc ON d.category_id = dc.id
        JOIN wp_drill_skills ds ON d.skill_id = ds.id
        LEFT JOIN wp_diagrams diag ON d.diagram_id = diag.id AND diag.is_active = 1
        LEFT JOIN wp_credit_to cr ON d.credit_id = cr.id AND cr.is_active = 1
        LEFT JOIN wp_achievement_types at ON d.achievement_type_id = at.id AND at.is_active = 1
        WHERE d.id = ? AND d.is_active = 1
    ");
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($drill = $result->fetch_assoc()) {
        // Log achievement information for debugging
        if ($drill['achievement_type_id']) {
            error_log("Single drill '{$drill['name']}' has achievement_type_id: {$drill['achievement_type_id']}, type: {$drill['achievement_type_name']}");
        }
        
        $this->sendSuccess($drill);
    } else {
        $this->sendError('Drill not found', 404);
    }
}



/**
 * NEW METHOD: Add this to your DrillAPI class
 * Get drill statistics for a specific user and drill
 */

public function getDrillStats($params = []) {
    try {
        $user_id = $params['user_id'] ?? 0;
        $drill_id = $params['drill_id'] ?? 0;
        $start_date = $params['start_date'] ?? null;
        $end_date = $params['end_date'] ?? null;
        
        if (!$user_id || !$drill_id) {
            $this->sendError('User ID and drill ID are required', 400);
            return;
        }
        
        $where = ["s.user_id = ?", "s.drill_id = ?"];
        $bindings = [$user_id, $drill_id];
        $types = "ii";
        
        // Add date filtering if provided
        if ($start_date) {
            $where[] = "s.practice_date >= ?";
            $bindings[] = $start_date;
            $types .= "s";
        }
        
        if ($end_date) {
            $where[] = "s.practice_date <= ?";
            $bindings[] = $end_date;
            $types .= "s";
        }
        
        // FIXED: Use a subquery to get drill info separately and use MIN() for non-aggregated columns
        $sql = "SELECT 
                    COUNT(*) as attempts,
                    MAX(s.score) as max_score,
                    AVG(s.score) as avg_score,
                    MIN(s.score) as min_score,
                    MIN(d.name) as drill_name,
                    MIN(d.max_score) as drill_max_score
                FROM wp_drill_scores s
                JOIN wp_drills d ON s.drill_id = d.id
                WHERE " . implode(' AND ', $where);
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param($types, ...$bindings);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stats = [
                'attempts' => (int)$row['attempts'],
                'max_score' => $row['max_score'] ? (int)round($row['max_score']) : 0,
                'avg_score' => $row['avg_score'] ? (int)round($row['avg_score']) : 0,
                'min_score' => $row['min_score'] ? (int)round($row['min_score']) : 0,
                'drill_name' => $row['drill_name'],
                'drill_max_score' => (int)$row['drill_max_score']
            ];
            
            $this->sendSuccess($stats);
        } else {
            // No scores found
            $this->sendSuccess([
                'attempts' => 0,
                'max_score' => 0,
                'avg_score' => 0,
                'min_score' => 0,
                'drill_name' => null,
                'drill_max_score' => 0
            ]);
        }
        
    } catch (Exception $e) {
        error_log("getDrillStats error: " . $e->getMessage());
        $this->sendError('Failed to get drill statistics: ' . $e->getMessage(), 500);
    }
}
    

/**
 * ENHANCED createDrill with achievement type support
 */
public function createDrill($data) {
    $name = trim($data['name'] ?? '');
    $category_id = $data['category_id'] ?? 0;
    $skill_id = $data['skill_id'] ?? 0;
    $description = trim($data['description'] ?? '');
    $instructions = trim($data['instructions'] ?? '');
    $max_score = $data['max_score'] ?? 10;
    $image_url = trim($data['image_url'] ?? '');
    $video_url = trim($data['video_url'] ?? '');
    $difficulty_rating = $data['difficulty_rating'] ?? 1.0;
    $estimated_time = $data['estimated_time_minutes'] ?? null;
    $color_code = trim($data['color_code'] ?? '#667eea');
    $credit_id = !empty($data['credit_id']) ? intval($data['credit_id']) : null;
    $achievement_type_id = !empty($data['achievement_type_id']) ? intval($data['achievement_type_id']) : null;
    
    if (empty($name) || !$category_id || !$skill_id) {
        $this->sendError('Name, category, and skill are required', 400);
        return;
    }
    
    // Verify credit exists if provided
    if ($credit_id !== null) {
        $creditCheck = $this->db->prepare("SELECT id FROM wp_credit_to WHERE id = ? AND is_active = 1");
        $creditCheck->bind_param('i', $credit_id);
        $creditCheck->execute();
        if ($creditCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid credit organization selected', 400);
            return;
        }
    }
    
    // Verify achievement type exists if provided
    if ($achievement_type_id !== null) {
        $achievementCheck = $this->db->prepare("SELECT id, name FROM wp_achievement_types WHERE id = ? AND is_active = 1");
        $achievementCheck->bind_param('i', $achievement_type_id);
        $achievementCheck->execute();
        $achievementResult = $achievementCheck->get_result();
        if ($achievementResult->num_rows === 0) {
            $this->sendError('Invalid achievement type selected', 400);
            return;
        }
        $achievementType = $achievementResult->fetch_assoc();
    }
    
    // ENHANCED SQL: Added achievement_type_id field
    $stmt = $this->db->prepare("
        INSERT INTO wp_drills 
        (name, category_id, skill_id, description, instructions, max_score, image_url, video_url, 
         difficulty_rating, estimated_time_minutes, color_code, credit_id, achievement_type_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param('siisssissisii', $name, $category_id, $skill_id, $description, $instructions, 
                     $max_score, $image_url, $video_url, $difficulty_rating, $estimated_time, 
                     $color_code, $credit_id, $achievement_type_id);
    
    if ($stmt->execute()) {
        $drill_id = $this->db->insert_id;
        error_log("Drill created successfully with ID: $drill_id, credit_id: " . ($credit_id ?? 'none') . 
                  ", achievement_type_id: " . ($achievement_type_id ?? 'none'));
        
        $response = [
            'id' => $drill_id, 
            'message' => 'Drill created successfully',
            'credit_assigned' => !empty($credit_id),
            'achievement_type_assigned' => !empty($achievement_type_id)
        ];
        
        if ($achievement_type_id) {
            $response['achievement_type_name'] = $achievementType['name'];
        }
        
        $this->sendSuccess($response);
    } else {
        $this->sendError('Failed to create drill: ' . $this->db->error, 500);
    }
}
   

/**
 * ENHANCED updateDrill with achievement type support
 */
public function updateDrill($id, $data) {
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $category_id = $data['category_id'] ?? 0;
    $skill_id = $data['skill_id'] ?? 0;
    $max_score = $data['max_score'] ?? 100;
    $diagram_id = isset($data['diagram_id']) ? ($data['diagram_id'] ?: null) : null;
    $credit_id = isset($data['credit_id']) ? (!empty($data['credit_id']) ? intval($data['credit_id']) : null) : null;
    $achievement_type_id = isset($data['achievement_type_id']) ? (!empty($data['achievement_type_id']) ? intval($data['achievement_type_id']) : null) : null;
    
    if (empty($name) || !$category_id || !$skill_id) {
        $this->sendError('Name, category, and skill are required', 400);
        return;
    }
    
    // Check if drill exists
    $checkStmt = $this->db->prepare("SELECT id FROM wp_drills WHERE id = ? AND is_active = 1");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows === 0) {
        $this->sendError('Drill not found', 404);
        return;
    }
    
    // Verify credit exists if provided
    if ($credit_id !== null) {
        $creditCheck = $this->db->prepare("SELECT id FROM wp_credit_to WHERE id = ? AND is_active = 1");
        $creditCheck->bind_param('i', $credit_id);
        $creditCheck->execute();
        if ($creditCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid credit organization selected', 400);
            return;
        }
    }
    
    // Verify achievement type exists if provided
    $achievementType = null;
    if ($achievement_type_id !== null) {
        $achievementCheck = $this->db->prepare("SELECT id, name FROM wp_achievement_types WHERE id = ? AND is_active = 1");
        $achievementCheck->bind_param('i', $achievement_type_id);
        $achievementCheck->execute();
        $achievementResult = $achievementCheck->get_result();
        if ($achievementResult->num_rows === 0) {
            $this->sendError('Invalid achievement type selected', 400);
            return;
        }
        $achievementType = $achievementResult->fetch_assoc();
    }
    
    // ENHANCED SQL: Added achievement_type_id field
    $stmt = $this->db->prepare("
        UPDATE wp_drills 
        SET name = ?, description = ?, category_id = ?, skill_id = ?, max_score = ?, 
            diagram_id = ?, credit_id = ?, achievement_type_id = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('ssiiiiiii', $name, $description, $category_id, $skill_id, $max_score, 
                     $diagram_id, $credit_id, $achievement_type_id, $id);
    
    if ($stmt->execute()) {
        error_log("Drill updated successfully - ID: $id, credit_id: " . ($credit_id ?? 'none') . 
                  ", achievement_type_id: " . ($achievement_type_id ?? 'none'));
        
        $response = [
            'message' => 'Drill updated successfully',
            'credit_assigned' => !empty($credit_id),
            'achievement_type_assigned' => !empty($achievement_type_id)
        ];
        
        if ($achievement_type_id && $achievementType) {
            $response['achievement_type_name'] = $achievementType['name'];
        }
        
        $this->sendSuccess($response);
    } else {
        $this->sendError('Failed to update drill: ' . $this->db->error, 500);
    }
}

/**
 * TRAINING PROGRAMS MANAGEMENT
 */

/**
 * Get all training programs with optional filtering
 */
public function getTrainingPrograms($params = []) {
    try {
        $where = ["tp.is_active = 1"];
        $bindings = [];
        $types = "";
        
        // Filter by category
        if (!empty($params['category_id'])) {
            $where[] = "tp.category_id = ?";
            $bindings[] = $params['category_id'];
            $types .= "i";
        }
        
        // Filter by skill
        if (!empty($params['skill_id'])) {
            $where[] = "tp.skill_id = ?";
            $bindings[] = $params['skill_id'];
            $types .= "i";
        }
        
        // Filter by difficulty level
        if (!empty($params['difficulty_level'])) {
            $where[] = "tp.difficulty_level = ?";
            $bindings[] = $params['difficulty_level'];
            $types .= "s";
        }
        
        // Filter by created_by
        if (!empty($params['created_by'])) {
            $where[] = "tp.created_by = ?";
            $bindings[] = $params['created_by'];
            $types .= "i";
        }
        
        // UPDATED SQL: Added credit information
        $sql = "SELECT tp.*, 
                       dc.display_name as category_display,
                       ds.display_name as skill_display,
                       creator.display_name as created_by_name,
                       (SELECT COUNT(*) FROM wp_training_program_units tpu 
                        WHERE tpu.program_id = tp.id AND tpu.is_active = 1) as unit_count,
                       
                       -- Credit information
                       c.organization_name as credit_organization_name,
                       c.website_url as credit_website_url,
                       c.icon_url as credit_icon_url
                       
                FROM wp_training_programs tp
                JOIN wp_drill_categories dc ON tp.category_id = dc.id
                JOIN wp_drill_skills ds ON tp.skill_id = ds.id
                JOIN wp_drill_users creator ON tp.created_by = creator.id
                LEFT JOIN wp_credit_to c ON tp.credit_id = c.id AND c.is_active = 1
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tp.created_at DESC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
            if (!$result) {
                throw new Exception('Query failed: ' . $this->db->error);
            }
        }
        
        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = $row;
        }
        
        error_log("getTrainingPrograms returning " . count($programs) . " programs with credit info");
        $this->sendSuccess($programs);
        
    } catch (Exception $e) {
        error_log("getTrainingPrograms error: " . $e->getMessage());
        $this->sendError('Failed to load training programs: ' . $e->getMessage(), 500);
    }
}

/**
 * Get a specific training program by ID
 */

public function getTrainingProgram($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT tp.*, 
                   dc.display_name as category_display,
                   ds.display_name as skill_display,
                   creator.display_name as created_by_name,
                   (SELECT COUNT(*) FROM wp_training_program_units tpu 
                    WHERE tpu.program_id = tp.id AND tpu.is_active = 1) as unit_count,
                   
                   -- Credit information
                   c.organization_name as credit_organization_name,
                   c.website_url as credit_website_url,
                   c.description as credit_description,
                   c.icon_url as credit_icon_url
                   
            FROM wp_training_programs tp
            JOIN wp_drill_categories dc ON tp.category_id = dc.id
            JOIN wp_drill_skills ds ON tp.skill_id = ds.id
            JOIN wp_drill_users creator ON tp.created_by = creator.id
            LEFT JOIN wp_credit_to c ON tp.credit_id = c.id AND c.is_active = 1
            WHERE tp.id = ? AND tp.is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($program = $result->fetch_assoc()) {
            $this->sendSuccess($program);
        } else {
            $this->sendError('Training program not found', 404);
        }
        
    } catch (Exception $e) {
        error_log("getTrainingProgram error: " . $e->getMessage());
        $this->sendError('Failed to load training program: ' . $e->getMessage(), 500);
    }
}

/**
 * Create a new training program
 */
public function createTrainingProgram($data) {
    try {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $category_id = $data['category_id'] ?? 0;
        $skill_id = $data['skill_id'] ?? 0;
        $difficulty_level = $data['difficulty_level'] ?? 'beginner';
        $estimated_duration_weeks = $data['estimated_duration_weeks'] ?? null;
        $created_by = $data['created_by'] ?? 0;
        $credit_id = !empty($data['credit_id']) ? intval($data['credit_id']) : null;
        
        // Validation
        if (empty($name) || !$category_id || !$skill_id || !$created_by) {
            $this->sendError('Name, category, skill, and creator are required', 400);
            return;
        }
        
        if (!in_array($difficulty_level, ['beginner', 'intermediate', 'advanced'])) {
            $this->sendError('Invalid difficulty level', 400);
            return;
        }
        
        // Verify category exists
        $categoryCheck = $this->db->prepare("SELECT id FROM wp_drill_categories WHERE id = ? AND is_active = 1");
        $categoryCheck->bind_param('i', $category_id);
        $categoryCheck->execute();
        if ($categoryCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid category selected', 400);
            return;
        }
        
        // Verify skill exists
        $skillCheck = $this->db->prepare("SELECT id FROM wp_drill_skills WHERE id = ? AND is_active = 1");
        $skillCheck->bind_param('i', $skill_id);
        $skillCheck->execute();
        if ($skillCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid skill selected', 400);
            return;
        }
        
        // Verify creator exists
        $creatorCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $creatorCheck->bind_param('i', $created_by);
        $creatorCheck->execute();
        if ($creatorCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid creator specified', 400);
            return;
        }
        
        // Verify credit exists if provided
        if ($credit_id !== null) {
            $creditCheck = $this->db->prepare("SELECT id FROM wp_credit_to WHERE id = ? AND is_active = 1");
            $creditCheck->bind_param('i', $credit_id);
            $creditCheck->execute();
            if ($creditCheck->get_result()->num_rows === 0) {
                $this->sendError('Invalid credit organization selected', 400);
                return;
            }
        }
        
        // UPDATED SQL: Added credit_id field
        $stmt = $this->db->prepare("
            INSERT INTO wp_training_programs 
            (name, description, category_id, skill_id, difficulty_level, estimated_duration_weeks, created_by, credit_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Insert prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('ssiissii', $name, $description, $category_id, $skill_id, $difficulty_level, $estimated_duration_weeks, $created_by, $credit_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert training program: ' . $this->db->error);
        }
        
        $program_id = $this->db->insert_id;
        error_log("Training program created successfully with ID: $program_id, credit_id: " . ($credit_id ?? 'none'));
        
        $this->sendSuccess([
            'id' => $program_id, 
            'message' => 'Training program created successfully',
            'credit_id' => $credit_id
        ]);
        
    } catch (Exception $e) {
        error_log("createTrainingProgram error: " . $e->getMessage());
        $this->sendError('Failed to create training program: ' . $e->getMessage(), 500);
    }
}

/**
 * Update an existing training program
 */
public function updateTrainingProgram($id, $data) {
    try {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $category_id = $data['category_id'] ?? 0;
        $skill_id = $data['skill_id'] ?? 0;
        $difficulty_level = $data['difficulty_level'] ?? 'beginner';
        $estimated_duration_weeks = $data['estimated_duration_weeks'] ?? null;
        $credit_id = isset($data['credit_id']) ? (!empty($data['credit_id']) ? intval($data['credit_id']) : null) : null;
        
        // Validation
        if (empty($name) || !$category_id || !$skill_id) {
            $this->sendError('Name, category, and skill are required', 400);
            return;
        }
        
        if (!in_array($difficulty_level, ['beginner', 'intermediate', 'advanced'])) {
            $this->sendError('Invalid difficulty level', 400);
            return;
        }
        
        // Check if program exists
        $checkStmt = $this->db->prepare("SELECT id FROM wp_training_programs WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $this->sendError('Training program not found', 404);
            return;
        }
        
        // Verify credit exists if provided
        if ($credit_id !== null) {
            $creditCheck = $this->db->prepare("SELECT id FROM wp_credit_to WHERE id = ? AND is_active = 1");
            $creditCheck->bind_param('i', $credit_id);
            $creditCheck->execute();
            if ($creditCheck->get_result()->num_rows === 0) {
                $this->sendError('Invalid credit organization selected', 400);
                return;
            }
        }
        
        // UPDATED SQL: Added credit_id field
        $stmt = $this->db->prepare("
            UPDATE wp_training_programs 
            SET name = ?, description = ?, category_id = ?, skill_id = ?, 
                difficulty_level = ?, estimated_duration_weeks = ?, credit_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('ssiissii', $name, $description, $category_id, $skill_id, $difficulty_level, $estimated_duration_weeks, $credit_id, $id);
        
        if ($stmt->execute()) {
            $this->sendSuccess([
                'message' => 'Training program updated successfully',
                'credit_id' => $credit_id
            ]);
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateTrainingProgram error: " . $e->getMessage());
        $this->sendError('Failed to update training program: ' . $e->getMessage(), 500);
    }
}


/**
 * Delete a training program (soft delete)
 */
public function deleteTrainingProgram($id) {
    try {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid training program ID', 400);
            return;
        }
        
        // Check if program exists
        $checkStmt = $this->db->prepare("SELECT id, name FROM wp_training_programs WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Training program not found', 404);
            return;
        }
        
        $program = $result->fetch_assoc();
        
        // Start transaction for safe deletion
        $this->db->begin_transaction();
        
        try {
            // Soft delete the program
            $stmt = $this->db->prepare("UPDATE wp_training_programs SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete training program: ' . $this->db->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('No changes made to training program');
            }
            
            // Also deactivate all units in this program
            $unitsStmt = $this->db->prepare("UPDATE wp_training_program_units SET is_active = 0 WHERE program_id = ?");
            $unitsStmt->bind_param('i', $id);
            $unitsStmt->execute();
            
            // Also deactivate all content in units of this program
            $contentStmt = $this->db->prepare("
                UPDATE wp_training_program_content 
                SET is_active = 0 
                WHERE unit_id IN (SELECT id FROM wp_training_program_units WHERE program_id = ?)
            ");
            $contentStmt->bind_param('i', $id);
            $contentStmt->execute();
            
            // Commit transaction
            $this->db->commit();
            
            $this->sendSuccess(['message' => "Training program '{$program['name']}' deleted successfully."]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("deleteTrainingProgram error: " . $e->getMessage());
        $this->sendError('Failed to delete training program: ' . $e->getMessage(), 500);
    }
}

/**
 * TRAINING PROGRAM UNITS MANAGEMENT
 */

/**
 * Get all units for a training program
 */
public function getTrainingProgramUnits($params = []) {
    try {
        $where = ["tpu.is_active = 1"];
        $bindings = [];
        $types = "";
        
        // Filter by program_id (required)
        if (!empty($params['program_id'])) {
            $where[] = "tpu.program_id = ?";
            $bindings[] = $params['program_id'];
            $types .= "i";
        } else {
            $this->sendError('Program ID is required', 400);
            return;
        }
        
        $sql = "SELECT tpu.*, tp.name as program_name
                FROM wp_training_program_units tpu
                JOIN wp_training_programs tp ON tpu.program_id = tp.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tpu.unit_order ASC, tpu.created_at ASC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
            if (!$result) {
                throw new Exception('Query failed: ' . $this->db->error);
            }
        }
        
        $units = [];
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
        
        error_log("getTrainingProgramUnits returning " . count($units) . " units for program " . $params['program_id']);
        $this->sendSuccess($units);
        
    } catch (Exception $e) {
        error_log("getTrainingProgramUnits error: " . $e->getMessage());
        $this->sendError('Failed to load training program units: ' . $e->getMessage(), 500);
    }
}

/**
 * Get a specific training program unit
 */
public function getTrainingProgramUnit($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT tpu.*, tp.name as program_name
            FROM wp_training_program_units tpu
            JOIN wp_training_programs tp ON tpu.program_id = tp.id
            WHERE tpu.id = ? AND tpu.is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($unit = $result->fetch_assoc()) {
            $this->sendSuccess($unit);
        } else {
            $this->sendError('Training program unit not found', 404);
        }
        
    } catch (Exception $e) {
        error_log("getTrainingProgramUnit error: " . $e->getMessage());
        $this->sendError('Failed to load training program unit: ' . $e->getMessage(), 500);
    }
}

/**
 * Create a new training program unit
 */
public function createTrainingProgramUnit($data) {
    try {
        $program_id = $data['program_id'] ?? 0;
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $unit_order = $data['unit_order'] ?? 1;
        $estimated_duration_days = $data['estimated_duration_days'] ?? null;
        
        // Validation
        if (!$program_id || empty($name)) {
            $this->sendError('Program ID and name are required', 400);
            return;
        }
        
        // Verify program exists
        $programCheck = $this->db->prepare("SELECT id FROM wp_training_programs WHERE id = ? AND is_active = 1");
        $programCheck->bind_param('i', $program_id);
        $programCheck->execute();
        if ($programCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid program specified', 400);
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_training_program_units 
            (program_id, name, description, unit_order, estimated_duration_days) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Insert prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('issii', $program_id, $name, $description, $unit_order, $estimated_duration_days);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert training program unit: ' . $this->db->error);
        }
        
        $unit_id = $this->db->insert_id;
        error_log("Training program unit created successfully with ID: $unit_id");
        
        $this->sendSuccess([
            'id' => $unit_id, 
            'message' => 'Training program unit created successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("createTrainingProgramUnit error: " . $e->getMessage());
        $this->sendError('Failed to create training program unit: ' . $e->getMessage(), 500);
    }
}

/**
 * Update an existing training program unit
 */
public function updateTrainingProgramUnit($id, $data) {
    try {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $unit_order = $data['unit_order'] ?? 1;
        $estimated_duration_days = $data['estimated_duration_days'] ?? null;
        
        // Validation
        if (empty($name)) {
            $this->sendError('Name is required', 400);
            return;
        }
        
        // Check if unit exists
        $checkStmt = $this->db->prepare("SELECT id FROM wp_training_program_units WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $this->sendError('Training program unit not found', 404);
            return;
        }
        
        $stmt = $this->db->prepare("
            UPDATE wp_training_program_units 
            SET name = ?, description = ?, unit_order = ?, estimated_duration_days = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('ssiii', $name, $description, $unit_order, $estimated_duration_days, $id);
        
        if ($stmt->execute()) {
            $this->sendSuccess(['message' => 'Training program unit updated successfully']);
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateTrainingProgramUnit error: " . $e->getMessage());
        $this->sendError('Failed to update training program unit: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete a training program unit (soft delete)
 */
public function deleteTrainingProgramUnit($id) {
    try {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid training program unit ID', 400);
            return;
        }
        
        // Check if unit exists
        $checkStmt = $this->db->prepare("SELECT id, name FROM wp_training_program_units WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Training program unit not found', 404);
            return;
        }
        
        $unit = $result->fetch_assoc();
        
        // Start transaction for safe deletion
        $this->db->begin_transaction();
        
        try {
            // Soft delete the unit
            $stmt = $this->db->prepare("UPDATE wp_training_program_units SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete training program unit: ' . $this->db->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('No changes made to training program unit');
            }
            
            // Also deactivate all content in this unit
            $contentStmt = $this->db->prepare("UPDATE wp_training_program_content SET is_active = 0 WHERE unit_id = ?");
            $contentStmt->bind_param('i', $id);
            $contentStmt->execute();
            
            // Commit transaction
            $this->db->commit();
            
            $this->sendSuccess(['message' => "Training program unit '{$unit['name']}' deleted successfully."]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("deleteTrainingProgramUnit error: " . $e->getMessage());
        $this->sendError('Failed to delete training program unit: ' . $e->getMessage(), 500);
    }
}

/**
 * TRAINING PROGRAM CONTENT MANAGEMENT
 */

/**
 * Get all training program content with optional filtering
 */
public function getTrainingProgramContents($params = []) {
    try {
        $where = ["tpc.is_active = 1"];
        $bindings = [];
        $types = "";
        
        // Filter by unit_id
        if (!empty($params['unit_id'])) {
            $where[] = "tpc.unit_id = ?";
            $bindings[] = $params['unit_id'];
            $types .= "i";
        }
        
        // Filter by content_type
        if (!empty($params['content_type'])) {
            $where[] = "tpc.content_type = ?";
            $bindings[] = $params['content_type'];
            $types .= "s";
        }
        
        // Filter by program_id (through unit relationship)
        if (!empty($params['program_id'])) {
            $where[] = "tpu.program_id = ?";
            $bindings[] = $params['program_id'];
            $types .= "i";
        }
        
        $sql = "SELECT tpc.*, 
                       tpu.name as unit_name,
                       tp.name as program_name,
                       
                       -- Drill fields (for drill content)
                       d.name as drill_name,
                       d.description as drill_description,
                       d.max_score as drill_max_score,
                       d.difficulty_rating as drill_difficulty,
                       dc1.display_name as drill_category,
                       ds1.display_name as drill_skill,
                       
                       -- Training content fields (for training content) - FIXED!
                       tc.name as content_name,
                       tc.description as content_description,
                       tc.content_type as content_type_display,
                       tc.difficulty_level as content_difficulty,
                       tc.file_size,
                       tc.original_filename,
                       tc.file_url,
                       tc.thumbnail_url,
                       dc2.display_name as content_category,
                       ds2.display_name as content_skill,
                       
                       -- Format file size
                       CASE 
                           WHEN tc.file_size IS NULL OR tc.file_size = 0 THEN ''
                           WHEN tc.file_size < 1024 THEN CONCAT(tc.file_size, ' B')
                           WHEN tc.file_size < 1048576 THEN CONCAT(ROUND(tc.file_size / 1024, 1), ' KB')
                           WHEN tc.file_size < 1073741824 THEN CONCAT(ROUND(tc.file_size / 1048576, 1), ' MB')
                           ELSE CONCAT(ROUND(tc.file_size / 1073741824, 1), ' GB')
                       END as file_size_formatted,
                       
                       creator.display_name as created_by_name
                       
                FROM wp_training_program_content tpc
                JOIN wp_training_program_units tpu ON tpc.unit_id = tpu.id
                JOIN wp_training_programs tp ON tpu.program_id = tp.id
                
                -- LEFT JOIN for drills (only when content_type = 'drill')
                LEFT JOIN wp_drills d ON tpc.drill_id = d.id AND tpc.content_type = 'drill'
                LEFT JOIN wp_drill_categories dc1 ON d.category_id = dc1.id AND tpc.content_type = 'drill'
                LEFT JOIN wp_drill_skills ds1 ON d.skill_id = ds1.id AND tpc.content_type = 'drill'
                
                -- LEFT JOIN for training content (only when content_type = 'training_content') - FIXED!
                LEFT JOIN wp_training_content tc ON tpc.content_id = tc.id AND tpc.content_type = 'training_content'
                LEFT JOIN wp_drill_categories dc2 ON tc.category_id = dc2.id AND tpc.content_type = 'training_content'
                LEFT JOIN wp_drill_skills ds2 ON tc.skill_id = ds2.id AND tpc.content_type = 'training_content'
                
                LEFT JOIN wp_drill_users creator ON tpc.created_by = creator.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tpc.unit_id, tpc.content_order";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
            if (!$result) {
                throw new Exception('Query failed: ' . $this->db->error);
            }
        }
        
        $contents = [];
        while ($row = $result->fetch_assoc()) {
            $contents[] = $row;
        }
        
        error_log("getTrainingProgramContents returning " . count($contents) . " content items");
        $this->sendSuccess($contents);
        
    } catch (Exception $e) {
        error_log("getTrainingProgramContents error: " . $e->getMessage());
        $this->sendError('Failed to load training program content: ' . $e->getMessage(), 500);
    }
}

/**
 * Get a specific training program content item
 */
public function getTrainingProgramContent($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT tpc.*, 
                   tpu.name as unit_name,
                   tp.name as program_name,
                   d.name as drill_name,
                   d.description as drill_description,
                   d.max_score as drill_max_score,
                   dc.display_name as drill_category,
                   ds.display_name as drill_skill,
                   creator.display_name as created_by_name
            FROM wp_training_program_content tpc
            JOIN wp_training_program_units tpu ON tpc.unit_id = tpu.id
            JOIN wp_training_programs tp ON tpu.program_id = tp.id
            LEFT JOIN wp_drills d ON tpc.drill_id = d.id AND tpc.content_type = 'drill'
            LEFT JOIN wp_drill_categories dc ON d.category_id = dc.id
            LEFT JOIN wp_drill_skills ds ON d.skill_id = ds.id
            LEFT JOIN wp_drill_users creator ON tpc.created_by = creator.id
            WHERE tpc.id = ? AND tpc.is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($content = $result->fetch_assoc()) {
            $this->sendSuccess($content);
        } else {
            $this->sendError('Training program content not found', 404);
        }
        
    } catch (Exception $e) {
        error_log("getTrainingProgramContent error: " . $e->getMessage());
        $this->sendError('Failed to load training program content: ' . $e->getMessage(), 500);
    }
}

/**
 * Create a new training program content item
 */
public function createTrainingProgramContent($data) {
    try {
        $unit_id = $data['unit_id'] ?? 0;
        $content_type = $data['content_type'] ?? 'drill';
        $drill_id = $data['drill_id'] ?? null;
        $content_title = trim($data['content_title'] ?? '');
        $content_description = trim($data['content_description'] ?? '');
        $content_data = $data['content_data'] ?? null;
        $content_order = $data['content_order'] ?? 1;
        $is_required = $data['is_required'] ?? 1;
        $estimated_duration_minutes = $data['estimated_duration_minutes'] ?? null;
        $points_possible = $data['points_possible'] ?? null;
        $created_by = $data['created_by'] ?? 1;
        
        // Validation
        if (!$unit_id) {
            $this->sendError('Unit ID is required', 400);
            return;
        }
        
        if ($content_type === 'drill' && !$drill_id) {
            $this->sendError('Drill ID is required for drill content type', 400);
            return;
        }
        
        if ($content_type !== 'drill' && empty($content_title)) {
            $this->sendError('Content title is required for non-drill content', 400);
            return;
        }
        
        // Verify unit exists
        $unitCheck = $this->db->prepare("SELECT id FROM wp_training_program_units WHERE id = ? AND is_active = 1");
        $unitCheck->bind_param('i', $unit_id);
        $unitCheck->execute();
        if ($unitCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid unit specified', 400);
            return;
        }
        
        // Verify drill exists if drill content
        if ($content_type === 'drill' && $drill_id) {
            $drillCheck = $this->db->prepare("SELECT id, max_score FROM wp_drills WHERE id = ? AND is_active = 1");
            $drillCheck->bind_param('i', $drill_id);
            $drillCheck->execute();
            $drillResult = $drillCheck->get_result();
            if ($drillResult->num_rows === 0) {
                $this->sendError('Invalid drill specified', 400);
                return;
            }
            
            // Auto-set points_possible from drill max_score if not provided
            if ($points_possible === null) {
                $drill = $drillResult->fetch_assoc();
                $points_possible = $drill['max_score'];
            }
        }
        
        // Convert content_data to JSON if it's an array
        if (is_array($content_data)) {
            $content_data = json_encode($content_data);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_training_program_content 
            (unit_id, content_type, drill_id, content_title, content_description, content_data, 
             content_order, is_required, estimated_duration_minutes, points_possible, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Insert prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('isisssiiiii', $unit_id, $content_type, $drill_id, $content_title, 
                         $content_description, $content_data, $content_order, $is_required, 
                         $estimated_duration_minutes, $points_possible, $created_by);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert training program content: ' . $this->db->error);
        }
        
        $content_id = $this->db->insert_id;
        error_log("Training program content created successfully with ID: $content_id");
        
        $this->sendSuccess([
            'id' => $content_id, 
            'message' => 'Training program content created successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("createTrainingProgramContent error: " . $e->getMessage());
        $this->sendError('Failed to create training program content: ' . $e->getMessage(), 500);
    }
}

/**
 * Create multiple training program content items (batch operation)
 */
/**
 * Create multiple training program content items (batch operation)
 * Handles both drill assignments and training content assignments
 */
public function createBatchTrainingProgramContent($data) {
    try {
        $unit_id = $data['unit_id'] ?? 0;
        $drill_ids = $data['drill_ids'] ?? [];
        $content_ids = $data['content_ids'] ?? [];
        $content_type = $data['content_type'] ?? 'drill';
        $created_by = $data['created_by'] ?? 1;
        
        if (!$unit_id) {
            $this->sendError('Unit ID is required', 400);
            return;
        }
        
        // Determine which type of content we're adding
        if ($content_type === 'training_content') {
            if (empty($content_ids) || !is_array($content_ids)) {
                $this->sendError('Content IDs array is required for training content', 400);
                return;
            }
            $items_to_process = $content_ids;
            $id_field = 'content_id';
        } else {
            if (empty($drill_ids) || !is_array($drill_ids)) {
                $this->sendError('Drill IDs array is required for drill content', 400);
                return;
            }
            $items_to_process = $drill_ids;
            $id_field = 'drill_id';
        }
        
        // Verify unit exists
        $unitCheck = $this->db->prepare("SELECT id FROM wp_training_program_units WHERE id = ? AND is_active = 1");
        $unitCheck->bind_param('i', $unit_id);
        $unitCheck->execute();
        if ($unitCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid unit specified', 400);
            return;
        }
        
        // Start transaction
        $this->db->begin_transaction();
        
        try {
            $created_items = [];
            $content_order = 1;
            
            // Get the highest existing content_order for this unit
            $orderStmt = $this->db->prepare("SELECT MAX(content_order) as max_order FROM wp_training_program_content WHERE unit_id = ? AND is_active = 1");
            $orderStmt->bind_param('i', $unit_id);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            if ($orderRow = $orderResult->fetch_assoc()) {
                $content_order = ($orderRow['max_order'] ?? 0) + 1;
            }
            
            foreach ($items_to_process as $item_id) {
                $item_id = (int)$item_id;
                
                if ($content_type === 'training_content') {
                    // Handle training content
                    $contentCheck = $this->db->prepare("SELECT id, name FROM wp_training_content WHERE id = ? AND is_active = 1");
                    $contentCheck->bind_param('i', $item_id);
                    $contentCheck->execute();
                    $contentResult = $contentCheck->get_result();
                    
                    if ($contentResult->num_rows === 0) {
                        error_log("Skipping invalid training content ID: $item_id");
                        continue;
                    }
                    
                    $content = $contentResult->fetch_assoc();
                    
                    // Check for duplicates
                    $duplicateCheck = $this->db->prepare("
                        SELECT id FROM wp_training_program_content 
                        WHERE unit_id = ? AND content_id = ? AND content_type = 'training_content' AND is_active = 1
                    ");
                    $duplicateCheck->bind_param('ii', $unit_id, $item_id);
                    $duplicateCheck->execute();
                    if ($duplicateCheck->get_result()->num_rows > 0) {
                        error_log("Skipping duplicate training content assignment: Unit $unit_id, Content $item_id");
                        continue;
                    }
                    
                    // Insert the content item
                    $insertStmt = $this->db->prepare("
                        INSERT INTO wp_training_program_content 
                        (unit_id, content_type, content_id, content_order, is_required, created_by) 
                        VALUES (?, 'training_content', ?, ?, 1, ?)
                    ");
                    
                    $insertStmt->bind_param('iiii', $unit_id, $item_id, $content_order, $created_by);
                    
                    if ($insertStmt->execute()) {
                        $created_items[] = [
                            'id' => $this->db->insert_id,
                            'content_id' => $item_id,
                            'content_name' => $content['name'],
                            'content_order' => $content_order
                        ];
                        $content_order++;
                    } else {
                        throw new Exception("Failed to insert training content $item_id: " . $this->db->error);
                    }
                    
                } else {
                    // Handle drill content (existing logic)
                    $drillCheck = $this->db->prepare("SELECT id, name, max_score, estimated_time_minutes FROM wp_drills WHERE id = ? AND is_active = 1");
                    $drillCheck->bind_param('i', $item_id);
                    $drillCheck->execute();
                    $drillResult = $drillCheck->get_result();
                    
                    if ($drillResult->num_rows === 0) {
                        error_log("Skipping invalid drill ID: $item_id");
                        continue;
                    }
                    
                    $drill = $drillResult->fetch_assoc();
                    
                    // Check for duplicates
                    $duplicateCheck = $this->db->prepare("
                        SELECT id FROM wp_training_program_content 
                        WHERE unit_id = ? AND drill_id = ? AND content_type = 'drill' AND is_active = 1
                    ");
                    $duplicateCheck->bind_param('ii', $unit_id, $item_id);
                    $duplicateCheck->execute();
                    if ($duplicateCheck->get_result()->num_rows > 0) {
                        error_log("Skipping duplicate drill assignment: Unit $unit_id, Drill $item_id");
                        continue;
                    }
                    
                    // Insert the drill content item
                    $insertStmt = $this->db->prepare("
                        INSERT INTO wp_training_program_content 
                        (unit_id, content_type, drill_id, content_order, is_required, 
                         estimated_duration_minutes, points_possible, created_by) 
                        VALUES (?, 'drill', ?, ?, 1, ?, ?, ?)
                    ");
                    
                    $insertStmt->bind_param('iiiiii', $unit_id, $item_id, $content_order, 
                                           $drill['estimated_time_minutes'], $drill['max_score'], $created_by);
                    
                    if ($insertStmt->execute()) {
                        $created_items[] = [
                            'id' => $this->db->insert_id,
                            'drill_id' => $item_id,
                            'drill_name' => $drill['name'],
                            'content_order' => $content_order
                        ];
                        $content_order++;
                    } else {
                        throw new Exception("Failed to insert drill $item_id: " . $this->db->error);
                    }
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            $content_type_label = $content_type === 'training_content' ? 'training content' : 'drill';
            $this->sendSuccess([
                'message' => count($created_items) . " $content_type_label assignments created successfully",
                'created_items' => $created_items,
                'unit_id' => $unit_id
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("createBatchTrainingProgramContent error: " . $e->getMessage());
        $this->sendError('Failed to create batch training program content: ' . $e->getMessage(), 500);
    }
}

/**
 * Update an existing training program content item
 */
public function updateTrainingProgramContent($id, $data) {
    try {
        $content_title = trim($data['content_title'] ?? '');
        $content_description = trim($data['content_description'] ?? '');
        $content_data = $data['content_data'] ?? null;
        $content_order = $data['content_order'] ?? 1;
        $is_required = $data['is_required'] ?? 1;
        $estimated_duration_minutes = $data['estimated_duration_minutes'] ?? null;
        $points_possible = $data['points_possible'] ?? null;
        
        // Check if content exists
        $checkStmt = $this->db->prepare("SELECT id, content_type FROM wp_training_program_content WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $this->sendError('Training program content not found', 404);
            return;
        }
        
        $existing = $checkResult->fetch_assoc();
        
        // Convert content_data to JSON if it's an array
        if (is_array($content_data)) {
            $content_data = json_encode($content_data);
        }
        
        $stmt = $this->db->prepare("
            UPDATE wp_training_program_content 
            SET content_title = ?, content_description = ?, content_data = ?, 
                content_order = ?, is_required = ?, estimated_duration_minutes = ?, 
                points_possible = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('sssiiihi', $content_title, $content_description, $content_data, 
                         $content_order, $is_required, $estimated_duration_minutes, 
                         $points_possible, $id);
        
        if ($stmt->execute()) {
            $this->sendSuccess(['message' => 'Training program content updated successfully']);
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateTrainingProgramContent error: " . $e->getMessage());
        $this->sendError('Failed to update training program content: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete a training program content item (soft delete)
 */
public function deleteTrainingProgramContent($id) {
    try {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid training program content ID', 400);
            return;
        }
        
        // Check if content exists
        $checkStmt = $this->db->prepare("SELECT id, content_title, drill_id FROM wp_training_program_content WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Training program content not found', 404);
            return;
        }
        
        $content = $result->fetch_assoc();
        
        // Soft delete the content
        $stmt = $this->db->prepare("UPDATE wp_training_program_content SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $itemName = $content['content_title'] ?: "Drill assignment (ID: {$content['drill_id']})";
                $this->sendSuccess(['message' => "Training program content '$itemName' deleted successfully."]);
            } else {
                $this->sendError('No changes made to training program content', 400);
            }
        } else {
            throw new Exception('Delete failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("deleteTrainingProgramContent error: " . $e->getMessage());
        $this->sendError('Failed to delete training program content: ' . $e->getMessage(), 500);
    }
}

/**
 * Get a specific content assignment by ID
 */
public function getContentAssignment($id) {
    $stmt = $this->db->prepare("
        SELECT ca.*, tc.name as content_name, tc.description as content_description,
               tc.content_type, tc.file_url, tc.external_url, tc.difficulty_level,
               dc.display_name as category_display,
               ds.display_name as skill_display,
               u.display_name as user_name, u.email as user_email,
               assigner.display_name as assigned_by_name
        FROM wp_training_content_assignments ca
        JOIN wp_training_content tc ON ca.content_id = tc.id
        JOIN wp_drill_categories dc ON tc.category_id = dc.id
        JOIN wp_drill_skills ds ON tc.skill_id = ds.id
        JOIN wp_drill_users u ON ca.user_id = u.id
        JOIN wp_drill_users assigner ON ca.assigned_by = assigner.id
        WHERE ca.id = ? AND ca.is_active = 1
    ");
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($assignment = $result->fetch_assoc()) {
        $this->sendSuccess($assignment);
    } else {
        $this->sendError('Content assignment not found', 404);
    }
}

/**
 * Get content assignments with enhanced filtering
 */
public function getContentAssignments($params = []) {
    $where = ["ca.is_active = 1"];
    $bindings = [];
    $types = "";
    
    if (!empty($params['user_id'])) {
        $where[] = "ca.user_id = ?";
        $bindings[] = $params['user_id'];
        $types .= "i";
    }
    
    if (!empty($params['content_id'])) {
        $where[] = "ca.content_id = ?";
        $bindings[] = $params['content_id'];
        $types .= "i";
    }
    
    if (!empty($params['assigned_by'])) {
        $where[] = "ca.assigned_by = ?";
        $bindings[] = $params['assigned_by'];
        $types .= "i";
    }
    
    if (isset($params['is_completed'])) {
        $where[] = "ca.is_completed = ?";
        $bindings[] = $params['is_completed'] ? 1 : 0;
        $types .= "i";
    }
    
    if (!empty($params['assigned_after'])) {
        $where[] = "ca.assigned_date >= ?";
        $bindings[] = $params['assigned_after'];
        $types .= "s";
    }
    
    if (!empty($params['due_before'])) {
        $where[] = "ca.due_date <= ?";
        $bindings[] = $params['due_before'];
        $types .= "s";
    }
    
    $sql = "SELECT ca.*, tc.name as content_name, tc.description as content_description,
                   tc.content_type, tc.file_url, tc.external_url, tc.difficulty_level,
                   dc.display_name as category_display,
                   ds.display_name as skill_display,
                   u.display_name as user_name, u.email as user_email,
                   assigner.display_name as assigned_by_name
            FROM wp_training_content_assignments ca
            JOIN wp_training_content tc ON ca.content_id = tc.id
            JOIN wp_drill_categories dc ON tc.category_id = dc.id
            JOIN wp_drill_skills ds ON tc.skill_id = ds.id
            JOIN wp_drill_users u ON ca.user_id = u.id
            JOIN wp_drill_users assigner ON ca.assigned_by = assigner.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ca.assigned_date DESC";
    
    if (!empty($bindings)) {
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$bindings);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $this->db->query($sql);
    }
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    $this->sendSuccess($assignments);
}

/**
 * Create content assignment
 */
public function createContentAssignment($data) {
    $user_id = $data['user_id'] ?? 0;
    $content_id = $data['content_id'] ?? 0;
    $assigned_by = $data['assigned_by'] ?? 0;
    $due_date = !empty($data['due_date']) ? $data['due_date'] : null;
    $notes = trim($data['notes'] ?? '');
    $coach_comments = trim($data['coach_comments'] ?? '');
    
    if (!$user_id || !$content_id || !$assigned_by) {
        $this->sendError('User ID, content ID, and assigned_by are required', 400);
    }
    
    // Verify user exists and is active
    $userCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
    $userCheck->bind_param('i', $user_id);
    $userCheck->execute();
    if ($userCheck->get_result()->num_rows === 0) {
        $this->sendError('User not found or inactive', 404);
    }
    
    // Verify content exists and is active
    $contentCheck = $this->db->prepare("SELECT id, name FROM wp_training_content WHERE id = ? AND is_active = 1");
    $contentCheck->bind_param('i', $content_id);
    $contentCheck->execute();
    $contentResult = $contentCheck->get_result();
    if ($contentResult->num_rows === 0) {
        $this->sendError('Training content not found or inactive', 404);
    }
    
    $content = $contentResult->fetch_assoc();
    
    // Check for duplicate active assignment
    $duplicateCheck = $this->db->prepare("
        SELECT id FROM wp_training_content_assignments 
        WHERE user_id = ? AND content_id = ? AND is_active = 1
    ");
    $duplicateCheck->bind_param('ii', $user_id, $content_id);
    $duplicateCheck->execute();
    if ($duplicateCheck->get_result()->num_rows > 0) {
        $this->sendError("This training content is already assigned to the user", 409);
    }
    
    $stmt = $this->db->prepare("
        INSERT INTO wp_training_content_assignments (user_id, content_id, assigned_by, due_date, notes, coach_comments) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('iiisss', $user_id, $content_id, $assigned_by, $due_date, $notes, $coach_comments);
    
    if ($stmt->execute()) {
        $assignment_id = $this->db->insert_id;
        
        $this->sendSuccess([
            'id' => $assignment_id, 
            'message' => "Training content '{$content['name']}' assigned successfully",
            'content_name' => $content['name']
        ]);
    } else {
        $this->sendError('Failed to create content assignment: ' . $this->db->error, 500);
    }
}

/**
 * Update content assignment
 */
public function updateContentAssignment($id, $data) {
    $checkStmt = $this->db->prepare("
        SELECT ca.*, tc.name as content_name, u.display_name as user_name 
        FROM wp_training_content_assignments ca
        JOIN wp_training_content tc ON ca.content_id = tc.id
        JOIN wp_drill_users u ON ca.user_id = u.id
        WHERE ca.id = ?
    ");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $this->sendError('Content assignment not found', 404);
    }
    
    $assignment = $result->fetch_assoc();
    
    // Handle soft delete
    if (isset($data['is_active']) && $data['is_active'] == 0) {
        $stmt = $this->db->prepare("UPDATE wp_training_content_assignments SET is_active = 0 WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $this->sendSuccess([
                'message' => "Assignment for '{$assignment['content_name']}' removed successfully"
            ]);
        } else {
            $this->sendError('Failed to remove content assignment: ' . $this->db->error, 500);
        }
        return;
    }
    
    // Handle other updates
    $due_date = !empty($data['due_date']) ? $data['due_date'] : null;
    $notes = trim($data['notes'] ?? $assignment['notes']);
    $coach_comments = trim($data['coach_comments'] ?? ($assignment['coach_comments'] ?? ''));
    $is_completed = isset($data['is_completed']) ? ($data['is_completed'] ? 1 : 0) : $assignment['is_completed'];
    $completed_date = $is_completed && !$assignment['is_completed'] ? date('Y-m-d H:i:s') : $assignment['completed_date'];
    
    $stmt = $this->db->prepare("
        UPDATE wp_training_content_assignments 
        SET due_date = ?, notes = ?, coach_comments = ?, is_completed = ?, completed_date = ?
        WHERE id = ?
    ");
    $stmt->bind_param('sssisi', $due_date, $notes, $coach_comments, $is_completed, $completed_date, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $this->sendSuccess(['message' => 'Content assignment updated successfully']);
        } else {
            $this->sendSuccess(['message' => 'No changes made to content assignment']);
        }
    } else {
        $this->sendError('Failed to update content assignment: ' . $this->db->error, 500);
    }
}

/**
 * Delete content assignment
 */
public function deleteContentAssignment($id) {
    $id = (int)$id;
    
    if ($id <= 0) {
        $this->sendError('Invalid content assignment ID', 400);
    }
    
    $checkStmt = $this->db->prepare("
        SELECT ca.*, tc.name as content_name, u.display_name as user_name 
        FROM wp_training_content_assignments ca
        JOIN wp_training_content tc ON ca.content_id = tc.id
        JOIN wp_drill_users u ON ca.user_id = u.id
        WHERE ca.id = ? AND ca.is_active = 1
    ");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $this->sendError('Content assignment not found or already deleted', 404);
    }
    
    $assignment = $result->fetch_assoc();
    
    $stmt = $this->db->prepare("UPDATE wp_training_content_assignments SET is_active = 0 WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $this->sendSuccess([
                'message' => "Assignment for '{$assignment['content_name']}' removed from {$assignment['user_name']} successfully"
            ]);
        } else {
            $this->sendError('Failed to remove content assignment', 500);
        }
    } else {
        $this->sendError('Failed to remove content assignment: ' . $this->db->error, 500);
    }
}


/**
 * Create the training program assignments table if it doesn't exist
 */
private function createTrainingProgramAssignmentsTable() {
    $sql = "CREATE TABLE IF NOT EXISTS wp_training_program_assignments (
        id INT NOT NULL AUTO_INCREMENT,
        program_id INT NOT NULL,
        user_id INT NOT NULL,
        assigned_by INT NOT NULL,
        assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        due_date DATE DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        completion_date TIMESTAMP NULL DEFAULT NULL,
        progress_percentage DECIMAL(5,2) DEFAULT 0.00,
        current_unit_id INT DEFAULT NULL,
        status ENUM('not_started', 'in_progress', 'completed', 'paused') DEFAULT 'not_started',
        notes TEXT,
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_program_assignment (program_id, user_id, is_active),
        FOREIGN KEY (program_id) REFERENCES wp_training_programs(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES wp_drill_users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES wp_drill_users(id),
        FOREIGN KEY (current_unit_id) REFERENCES wp_training_program_units(id) ON DELETE SET NULL,
        KEY idx_program_assignments_program (program_id),
        KEY idx_program_assignments_user (user_id),
        KEY idx_program_assignments_assigned_by (assigned_by),
        KEY idx_program_assignments_status (status),
        KEY idx_program_assignments_current_unit (current_unit_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    if (!$this->db->query($sql)) {
        error_log("Failed to create wp_training_program_assignments table: " . $this->db->error);
        throw new Exception("Failed to create training program assignments table: " . $this->db->error);
    }
    
    error_log("Training program assignments table created/verified successfully");
}

/**
 * Get a specific training program assignment by ID
 */
public function getTrainingProgramAssignment($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT tpa.*, 
                   tp.name as program_name,
                   tp.description as program_description,
                   tp.difficulty_level,
                   tp.estimated_duration_weeks,
                   dc.display_name as program_category,
                   ds.display_name as program_skill,
                   u.display_name as student_name,
                   u.email as student_email,
                   coach.display_name as coach_name,
                   current_unit.name as current_unit_name,
                   (SELECT COUNT(*) FROM wp_training_program_units tpu 
                    WHERE tpu.program_id = tp.id AND tpu.is_active = 1) as total_units,
                   
                   -- NEW: Check if this assignment has snapshot data
                   (SELECT COUNT(*) FROM wp_training_program_assigned tpa_snap 
                    WHERE tpa_snap.assignment_id = tpa.id AND tpa_snap.is_active = 1) as has_snapshot
                    
            FROM wp_training_program_assignments tpa
            JOIN wp_training_programs tp ON tpa.program_id = tp.id
            JOIN wp_drill_categories dc ON tp.category_id = dc.id
            JOIN wp_drill_skills ds ON tp.skill_id = ds.id
            JOIN wp_drill_users u ON tpa.user_id = u.id
            JOIN wp_drill_users coach ON tpa.assigned_by = coach.id
            LEFT JOIN wp_training_program_units current_unit ON tpa.current_unit_id = current_unit.id
            WHERE tpa.id = ? AND tpa.is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($assignment = $result->fetch_assoc()) {
            // Add some computed fields for better frontend handling
            $assignment['has_snapshot'] = (bool)$assignment['has_snapshot'];
            $assignment['progress_percentage'] = (float)$assignment['progress_percentage'];
            $assignment['assignment_sequence'] = (int)$assignment['assignment_sequence'];
            
            $this->sendSuccess($assignment);
        } else {
            $this->sendError('Training program assignment not found', 404);
        }
        
    } catch (Exception $e) {
        error_log("getTrainingProgramAssignment error: " . $e->getMessage());
        $this->sendError('Failed to load training program assignment: ' . $e->getMessage(), 500);
    }
}

/**
 * ALSO, here's the updated getTrainingProgramAssignments method with sequence support
 * Replace your existing getTrainingProgramAssignments method with this enhanced version:
 */
public function getTrainingProgramAssignments($params = []) {
    try {
        $where = ["tpa.is_active = 1"];
        $bindings = [];
        $types = "";
        
        // Filter by coach (assigned_by)
        if (!empty($params['coach_id'])) {
            $where[] = "tpa.assigned_by = ?";
            $bindings[] = $params['coach_id'];
            $types .= "i";
        }
        
        // Filter by user (student)
        if (!empty($params['user_id'])) {
            $where[] = "tpa.user_id = ?";
            $bindings[] = $params['user_id'];
            $types .= "i";
        }
        
        // Filter by program
        if (!empty($params['program_id'])) {
            $where[] = "tpa.program_id = ?";
            $bindings[] = $params['program_id'];
            $types .= "i";
        }
        
        // Filter by status
        if (!empty($params['status'])) {
            $where[] = "tpa.status = ?";
            $bindings[] = $params['status'];
            $types .= "s";
        }
        
        // NEW: Filter by assignment sequence
        if (!empty($params['assignment_sequence'])) {
            $where[] = "tpa.assignment_sequence = ?";
            $bindings[] = $params['assignment_sequence'];
            $types .= "i";
        }
        
        // NEW: Include inactive assignments if requested (for history)
        if (!empty($params['include_inactive'])) {
            // Remove the is_active filter
            $where = array_filter($where, function($condition) {
                return strpos($condition, 'tpa.is_active') === false;
            });
        }
        
        $sql = "SELECT tpa.*, 
                       tp.name as program_name,
                       tp.description as program_description,
                       tp.difficulty_level,
                       tp.estimated_duration_weeks,
                       dc.display_name as program_category,
                       ds.display_name as program_skill,
                       u.display_name as student_name,
                       u.email as student_email,
                       coach.display_name as coach_name,
                       current_unit.name as current_unit_name,
                       (SELECT COUNT(*) FROM wp_training_program_units tpu 
                        WHERE tpu.program_id = tp.id AND tpu.is_active = 1) as total_units,
                       
                       -- NEW: Check if this assignment has snapshot data
                       (SELECT COUNT(*) FROM wp_training_program_assigned tpa_snap 
                        WHERE tpa_snap.assignment_id = tpa.id AND tpa_snap.is_active = 1) as has_snapshot,
                       
                       -- NEW: Get snapshot-based locking mode if available
                       (SELECT 
                            CASE 
                                WHEN COUNT(CASE WHEN ua.is_locked = 0 THEN 1 END) = COUNT(*) THEN 'unlocked'
                                WHEN COUNT(CASE WHEN ua.is_locked = 1 THEN 1 END) = COUNT(*) THEN 'locked'
                                ELSE 'progressive'
                            END
                        FROM wp_training_program_units_assigned ua 
                        WHERE ua.assignment_id = tpa.id AND ua.is_active = 1) as locking_mode
                        
                FROM wp_training_program_assignments tpa
                JOIN wp_training_programs tp ON tpa.program_id = tp.id
                JOIN wp_drill_categories dc ON tp.category_id = dc.id
                JOIN wp_drill_skills ds ON tp.skill_id = ds.id
                JOIN wp_drill_users u ON tpa.user_id = u.id
                JOIN wp_drill_users coach ON tpa.assigned_by = coach.id
                LEFT JOIN wp_training_program_units current_unit ON tpa.current_unit_id = current_unit.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tpa.assigned_date DESC, tpa.assignment_sequence DESC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
            if (!$result) {
                throw new Exception('Query failed: ' . $this->db->error);
            }
        }
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            // Add some computed fields for better frontend handling
            $row['has_snapshot'] = (bool)$row['has_snapshot'];
            $row['progress_percentage'] = (float)$row['progress_percentage'];
            $row['assignment_sequence'] = (int)$row['assignment_sequence'];
            
            $assignments[] = $row;
        }
        
        $this->sendSuccess($assignments);
        
    } catch (Exception $e) {
        error_log("getTrainingProgramAssignments error: " . $e->getMessage());
        $this->sendError('Failed to load training program assignments: ' . $e->getMessage(), 500);
    }
}

/**
 * Create a new training program assignment - Updated with snapshot stuff
 */
public function createTrainingProgramAssignment($data) {
    try {
        $program_id = $data['program_id'] ?? 0;
        $user_id = $data['user_id'] ?? 0;
        $assigned_by = $data['assigned_by'] ?? 0;
        $due_date = $data['due_date'] ?? null;
        $start_date = $data['start_date'] ?? date('Y-m-d');
        $notes = trim($data['notes'] ?? '');
        
        // NEW: Handle assignment sequence
        $assignment_sequence = $data['assignment_sequence'] ?? null;
        if ($assignment_sequence === null) {
            // Auto-generate the next sequence number
            $assignment_sequence = $this->getNextSequenceNumber($user_id, $program_id);
        }
        
        // NEW: Phase 2C - Check for snapshot creation flag
        $create_snapshot = isset($data['create_snapshot']) && $data['create_snapshot'] === true;
        $locking_mode = $data['locking_mode'] ?? 'unlocked';
        
        error_log("createTrainingProgramAssignment: sequence=$assignment_sequence, create_snapshot=" . 
                  ($create_snapshot ? 'true' : 'false') . ", locking_mode=$locking_mode");
        
        // Validation
        if (!$program_id || !$user_id || !$assigned_by) {
            $this->sendError('Program ID, user ID, and assigned_by are required', 400);
            return;
        }
        
        // Verify program exists
        $programCheck = $this->db->prepare("SELECT id, name FROM wp_training_programs WHERE id = ? AND is_active = 1");
        $programCheck->bind_param('i', $program_id);
        $programCheck->execute();
        $programResult = $programCheck->get_result();
        if ($programResult->num_rows === 0) {
            $this->sendError('Invalid program selected', 400);
            return;
        }
        $program = $programResult->fetch_assoc();
        
        // Verify user exists
        $userCheck = $this->db->prepare("SELECT id, display_name FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $userCheck->bind_param('i', $user_id);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid user selected', 400);
            return;
        }
        
        // Check for existing assignment with same sequence (this should not happen with auto-generation)
        $existingCheck = $this->db->prepare("
            SELECT id FROM wp_training_program_assignments 
            WHERE program_id = ? AND user_id = ? AND assignment_sequence = ?
        ");
        $existingCheck->bind_param('iii', $program_id, $user_id, $assignment_sequence);
        $existingCheck->execute();
        if ($existingCheck->get_result()->num_rows > 0) {
            $this->sendError("Assignment sequence #$assignment_sequence already exists for this user/program", 409);
            return;
        }
        
        // Start transaction
        $this->db->begin_transaction();
        
        try {
            // Create the assignment with sequence number
            $stmt = $this->db->prepare("
                INSERT INTO wp_training_program_assignments 
                (program_id, user_id, assignment_sequence, assigned_by, due_date, start_date, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception('Insert prepare failed: ' . $this->db->error);
            }
            
            $stmt->bind_param('iiissss', $program_id, $user_id, $assignment_sequence, $assigned_by, $due_date, $start_date, $notes);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create assignment: ' . $this->db->error);
            }
            
            $assignment_id = $this->db->insert_id;
            error_log("Assignment created with ID: $assignment_id, sequence: $assignment_sequence");
            
            // NEW: Create snapshot records if flag is set
            if ($create_snapshot) {
                error_log("Creating snapshot records for assignment ID: $assignment_id");
                $this->createAssignmentSnapshot($assignment_id, $program_id, $user_id, $locking_mode);
            }
            
            // Commit transaction
            $this->db->commit();
            
            $message = "Assignment sequence #$assignment_sequence created successfully";
            if ($create_snapshot) {
                $message .= ' with snapshot data';
            }
            
            $this->sendSuccess([
                'id' => $assignment_id,
                'assignment_sequence' => $assignment_sequence,
                'message' => $message,
                'snapshot_created' => $create_snapshot,
                'locking_mode' => $locking_mode
            ]);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("createTrainingProgramAssignment error: " . $e->getMessage());
        $this->sendError('Failed to create training program assignment: ' . $e->getMessage(), 500);
    }
}

/**
 * Create snapshot records for an assignment
 * This copies program structure into the "assigned" tables
 */

private function createAssignmentSnapshot($assignment_id, $program_id, $user_id, $locking_mode) {
    try {
        error_log("Creating assignment snapshot - assignment_id=$assignment_id, locking_mode=$locking_mode");
        
        // STEP 1: Create program snapshot record
        $programStmt = $this->db->prepare("
            INSERT INTO wp_training_program_assigned 
            (assignment_id, original_program_id, user_id, name, description, category_id, skill_id, 
             difficulty_level, estimated_duration_weeks, created_by)
            SELECT ?, tp.id, ?, tp.name, tp.description, tp.category_id, tp.skill_id, 
                   tp.difficulty_level, tp.estimated_duration_weeks, tp.created_by
            FROM wp_training_programs tp 
            WHERE tp.id = ? AND tp.is_active = 1
        ");
        
        if (!$programStmt) {
            throw new Exception('Program snapshot prepare failed: ' . $this->db->error);
        }
        
        $programStmt->bind_param('iii', $assignment_id, $user_id, $program_id);
        
        if (!$programStmt->execute()) {
            throw new Exception('Failed to create program snapshot: ' . $this->db->error);
        }
        
        $assigned_program_id = $this->db->insert_id;
        error_log("Program snapshot created with ID: $assigned_program_id");
        
        // STEP 2: Create unit snapshots with FIXED locking logic
        $unitsStmt = $this->db->prepare("
            SELECT * FROM wp_training_program_units 
            WHERE program_id = ? AND is_active = 1 
            ORDER BY unit_order ASC
        ");
        $unitsStmt->bind_param('i', $program_id);
        $unitsStmt->execute();
        $unitsResult = $unitsStmt->get_result();
        
        $unitPosition = 0; // FIXED: Track position starting from 0
        $assignedUnitIds = [];
        $originalUnitIds = [];
        
        while ($unit = $unitsResult->fetch_assoc()) {
            $unitPosition++; // FIXED: Increment to get 1, 2, 3, etc.
            $originalUnitIds[] = $unit['id'];
            
            // FIXED: Use position (1, 2, 3...) not the database unit_order
            $isLocked = $this->calculateUnitLockStatus($locking_mode, $unitPosition);
            
            error_log("Unit #$unitPosition (order={$unit['unit_order']}) - locking_mode=$locking_mode, isLocked=$isLocked");
            
            $unitInsertStmt = $this->db->prepare("
                INSERT INTO wp_training_program_units_assigned 
                (assignment_id, original_unit_id, name, description, unit_order, 
                 estimated_duration_days, is_locked)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$unitInsertStmt) {
                throw new Exception('Unit snapshot prepare failed: ' . $this->db->error);
            }
            
            $unitInsertStmt->bind_param('iissiii', $assignment_id, $unit['id'], 
                                       $unit['name'], $unit['description'], $unit['unit_order'], 
                                       $unit['estimated_duration_days'], $isLocked);
            
            if (!$unitInsertStmt->execute()) {
                throw new Exception('Failed to create unit snapshot: ' . $this->db->error);
            }
            
            $assignedUnitId = $this->db->insert_id;
            $assignedUnitIds[] = $assignedUnitId;
            
            error_log("Unit snapshot created - ID: $assignedUnitId, locked: " . ($isLocked ? 'yes' : 'no'));
        }
        
        // STEP 3: Create content snapshots for each unit
        foreach ($assignedUnitIds as $index => $assignedUnitId) {
            $originalUnitId = $originalUnitIds[$index];
            $this->createContentSnapshots($assignment_id, $assignedUnitId, $originalUnitId);
        }
        
        error_log("Snapshot creation completed - $unitPosition units processed with mode: $locking_mode");
        
    } catch (Exception $e) {
        error_log("createAssignmentSnapshot error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * NEW METHOD: Calculate whether a unit should be locked based on locking mode
 */

private function calculateUnitLockStatus($lockingMode, $unitPosition) {
    error_log("calculateUnitLockStatus: mode=$lockingMode, position=$unitPosition");
    
    switch ($lockingMode) {
        case 'unlocked':
            return 0; // All units unlocked
            
        case 'progressive':
            // FIXED: First unit (position 1) unlocked, rest locked
            return ($unitPosition === 1) ? 0 : 1;
            
        case 'locked':
            return 1; // All units locked
            
        default:
            error_log("Unknown locking mode '$lockingMode', defaulting to unlocked");
            return 0;
    }
}

/**
 * NEW METHOD: Create content snapshots for a unit - FINAL FIX WITH CORRECT COLUMN NAME
 */
private function createContentSnapshots($assignment_id, $assignedUnitId, $originalUnitId) {
    try {
        $contentStmt = $this->db->prepare("
            SELECT * FROM wp_training_program_content 
            WHERE unit_id = ? AND is_active = 1 
            ORDER BY content_order ASC
        ");
        $contentStmt->bind_param('i', $originalUnitId);
        $contentStmt->execute();
        $contentResult = $contentStmt->get_result();
        
        $contentCount = 0;
        
        while ($content = $contentResult->fetch_assoc()) {
            // FINAL FIX: Using 'assigned_unit_id' (not 'unit_assigned_id')
            $contentInsertStmt = $this->db->prepare("
                INSERT INTO wp_training_program_content_assigned 
                (assignment_id, assigned_unit_id, original_content_id, content_type, drill_id, content_id, 
                 content_title, content_description, content_data, content_order, 
                 is_required, estimated_duration_minutes, points_possible)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$contentInsertStmt) {
                throw new Exception('Content snapshot prepare failed: ' . $this->db->error);
            }
            
		$contentInsertStmt->bind_param('iiissssssiiii', 
			$assignment_id,           // assignment_id (i)
			$assignedUnitId,         // assigned_unit_id (i)
			$content['id'],          // original_content_id (i)
			$content['content_type'], // content_type (s)
			$content['drill_id'],    // drill_id (s) - can be NULL
			$content['content_id'],  // content_id (s) - can be NULL
			$content['content_title'], // content_title (s)
			$content['content_description'], // content_description (s)
			$content['content_data'], // content_data (s)
			$content['content_order'], // content_order (i)
			$content['is_required'], // is_required (i)
			$content['estimated_duration_minutes'], // estimated_duration_minutes (i)
			$content['points_possible'] // points_possible (i)
		);
            
            if (!$contentInsertStmt->execute()) {
                throw new Exception('Failed to create content snapshot: ' . $this->db->error);
            }
            
            $contentCount++;
        }
        
        error_log("Phase 2C: Created $contentCount content snapshots for assigned unit ID: $assignedUnitId");
        
    } catch (Exception $e) {
        error_log("Phase 2C: createContentSnapshots error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Update a training program assignment
 */
public function updateTrainingProgramAssignment($id, $data) {
    try {
        $due_date = $data['due_date'] ?? null;
        $start_date = $data['start_date'] ?? null;
        $completion_date = $data['completion_date'] ?? null;
        $progress_percentage = $data['progress_percentage'] ?? null;
        $current_unit_id = $data['current_unit_id'] ?? null;
        $status = $data['status'] ?? null;
        $notes = trim($data['notes'] ?? '');
        
        // Check if assignment exists
        $checkStmt = $this->db->prepare("SELECT id FROM wp_training_program_assignments WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $this->sendError('Training program assignment not found', 404);
            return;
        }
        
        // Build dynamic update query
        $updateFields = [];
        $updateValues = [];
        $updateTypes = '';
        
        if ($due_date !== null) {
            $updateFields[] = "due_date = ?";
            $updateValues[] = $due_date;
            $updateTypes .= "s";
        }
        
        if ($start_date !== null) {
            $updateFields[] = "start_date = ?";
            $updateValues[] = $start_date;
            $updateTypes .= "s";
        }
        
        if ($completion_date !== null) {
            $updateFields[] = "completion_date = ?";
            $updateValues[] = $completion_date;
            $updateTypes .= "s";
        }
        
        if ($progress_percentage !== null) {
            $updateFields[] = "progress_percentage = ?";
            $updateValues[] = $progress_percentage;
            $updateTypes .= "d";
        }
        
        if ($current_unit_id !== null) {
            $updateFields[] = "current_unit_id = ?";
            $updateValues[] = $current_unit_id;
            $updateTypes .= "i";
        }
        
        if ($status !== null) {
            $updateFields[] = "status = ?";
            $updateValues[] = $status;
            $updateTypes .= "s";
        }
        
        if ($notes !== '') {
            $updateFields[] = "notes = ?";
            $updateValues[] = $notes;
            $updateTypes .= "s";
        }
        
        if (empty($updateFields)) {
            $this->sendError('No fields to update', 400);
            return;
        }
        
        $updateFields[] = "updated_at = NOW()";
        $updateValues[] = $id;
        $updateTypes .= "i";
        
        $sql = "UPDATE wp_training_program_assignments SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param($updateTypes, ...$updateValues);
        
        if ($stmt->execute()) {
            $this->sendSuccess(['message' => 'Training program assignment updated successfully']);
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateTrainingProgramAssignment error: " . $e->getMessage());
        $this->sendError('Failed to update training program assignment: ' . $e->getMessage(), 500);
    }
}

/**
 * UPDATE YOUR EXISTING deleteTrainingProgramAssignment METHOD
 * Replace your existing deleteTrainingProgramAssignment method with this version:
 */
public function deleteTrainingProgramAssignment($id) {
    try {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid assignment ID', 400);
            return;
        }
        
        // Check if assignment exists
        $checkStmt = $this->db->prepare("
            SELECT tpa.id, tp.name as program_name, u.display_name as student_name
            FROM wp_training_program_assignments tpa
            JOIN wp_training_programs tp ON tpa.program_id = tp.id
            JOIN wp_drill_users u ON tpa.user_id = u.id
            WHERE tpa.id = ? AND tpa.is_active = 1
        ");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Training program assignment not found', 404);
            return;
        }
        
        $assignment = $result->fetch_assoc();
        
        // Start transaction for safe deletion
        $this->db->begin_transaction();
        
        try {
            // STEP 1: Soft delete the main assignment
            $stmt = $this->db->prepare("UPDATE wp_training_program_assignments SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete training program assignment: ' . $this->db->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('No changes made to training program assignment');
            }
            
            // STEP 2: Clean up snapshot records if they exist
            $this->cleanupAssignmentSnapshot($id);
            
            // Commit transaction
            $this->db->commit();
            
            $this->sendSuccess([
                'message' => "Training program assignment removed successfully: '{$assignment['program_name']}' from {$assignment['student_name']}"
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("deleteTrainingProgramAssignment error: " . $e->getMessage());
        $this->sendError('Failed to delete training program assignment: ' . $e->getMessage(), 500);
    }
}

/**
 * Get assigned units for a specific assignment (snapshot data)
 */
public function getTrainingProgramUnitsAssigned($params = []) {
    try {
        $assignment_id = $params['assignment_id'] ?? 0;
        
        if (!$assignment_id) {
            $this->sendError('Assignment ID is required', 400);
            return;
        }
        
        $stmt = $this->db->prepare("
            SELECT ua.*, 
                   tpa.user_id,
                   tp.name as program_name
            FROM wp_training_program_units_assigned ua
            JOIN wp_training_program_assignments tpa ON ua.assignment_id = tpa.id
            JOIN wp_training_programs tp ON tpa.program_id = tp.id
            WHERE ua.assignment_id = ? AND ua.is_active = 1
            ORDER BY ua.unit_order ASC
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $units = [];
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
        
        error_log("getTrainingProgramUnitsAssigned returning " . count($units) . " units for assignment " . $assignment_id);
        $this->sendSuccess($units);
        
    } catch (Exception $e) {
        error_log("getTrainingProgramUnitsAssigned error: " . $e->getMessage());
        $this->sendError('Failed to load assigned units: ' . $e->getMessage(), 500);
    }
}

/**
 * Get assigned content for a specific assigned unit (snapshot data)
 */
public function getTrainingProgramContentAssigned($params = []) {
    try {
        $assigned_unit_id = $params['assigned_unit_id'] ?? 0;
        
        if (!$assigned_unit_id) {
            $this->sendError('Assigned unit ID is required', 400);
            return;
        }
        
        $stmt = $this->db->prepare("
            SELECT ca.*, 
                   ua.name as unit_name,
                   
                   -- Drill fields (for drill content)
                   d.name as drill_name,
                   d.description as drill_description,
                   d.max_score as drill_max_score,
                   d.difficulty_rating as drill_difficulty,
                   dc1.display_name as drill_category,
                   ds1.display_name as drill_skill,
                   
                   -- Training content fields (for training content)
                   tc.name as content_name,
                   tc.description as content_description,
                   tc.content_type as content_type_display,
                   tc.difficulty_level as content_difficulty,
                   tc.file_size,
                   tc.original_filename,
                   tc.file_url,
                   tc.thumbnail_url,
                   dc2.display_name as content_category,
                   ds2.display_name as content_skill,
                   
                   -- Format file size
                   CASE 
                       WHEN tc.file_size IS NULL OR tc.file_size = 0 THEN ''
                       WHEN tc.file_size < 1024 THEN CONCAT(tc.file_size, ' B')
                       WHEN tc.file_size < 1048576 THEN CONCAT(ROUND(tc.file_size / 1024, 1), ' KB')
                       WHEN tc.file_size < 1073741824 THEN CONCAT(ROUND(tc.file_size / 1048576, 1), ' MB')
                       ELSE CONCAT(ROUND(tc.file_size / 1073741824, 1), ' GB')
                   END as file_size_formatted
                   
            FROM wp_training_program_content_assigned ca
            JOIN wp_training_program_units_assigned ua ON ca.assigned_unit_id = ua.id
            
            -- LEFT JOIN for drills (only when content_type = 'drill')
            LEFT JOIN wp_drills d ON ca.drill_id = d.id AND ca.content_type = 'drill'
            LEFT JOIN wp_drill_categories dc1 ON d.category_id = dc1.id AND ca.content_type = 'drill'
            LEFT JOIN wp_drill_skills ds1 ON d.skill_id = ds1.id AND ca.content_type = 'drill'
            
            -- LEFT JOIN for training content (only when content_type = 'training_content')
            LEFT JOIN wp_training_content tc ON ca.content_id = tc.id AND ca.content_type = 'training_content'
            LEFT JOIN wp_drill_categories dc2 ON tc.category_id = dc2.id AND ca.content_type = 'training_content'
            LEFT JOIN wp_drill_skills ds2 ON tc.skill_id = ds2.id AND ca.content_type = 'training_content'
            
            WHERE ca.assigned_unit_id = ? AND ca.is_active = 1
            ORDER BY ca.content_order ASC
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $assigned_unit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $content = [];
        while ($row = $result->fetch_assoc()) {
            $content[] = $row;
        }
        
        error_log("getTrainingProgramContentAssigned returning " . count($content) . " content items for assigned unit " . $assigned_unit_id);
        $this->sendSuccess($content);
        
    } catch (Exception $e) {
        error_log("getTrainingProgramContentAssigned error: " . $e->getMessage());
        $this->sendError('Failed to load assigned content: ' . $e->getMessage(), 500);
    }
}

/**
 * NEW METHOD: Clean up snapshot records for an assignment - FIXED VERSION
 */
private function cleanupAssignmentSnapshot($assignment_id) {
    try {
        error_log("Phase 2C: Cleaning up snapshot records for assignment ID: $assignment_id");
        
        // STEP 1: Find the assigned program record
        $programStmt = $this->db->prepare("
            SELECT id FROM wp_training_program_assigned 
            WHERE assignment_id = ? AND is_active = 1
        ");
        
        if (!$programStmt) {
            error_log("Phase 2C: Failed to prepare program query: " . $this->db->error);
            return; // Don't throw - this is cleanup, main operation should still succeed
        }
        
        $programStmt->bind_param('i', $assignment_id);
        $programStmt->execute();
        $programResult = $programStmt->get_result();
        
        if ($programRow = $programResult->fetch_assoc()) {
            $assigned_program_id = $programRow['id'];
            
            // STEP 2: Find assigned units
            $unitsStmt = $this->db->prepare("
                SELECT id FROM wp_training_program_units_assigned 
                WHERE assignment_id = ? AND is_active = 1
            ");
            
            if (!$unitsStmt) {
                error_log("Phase 2C: Failed to prepare units query: " . $this->db->error);
                return;
            }
            
            $unitsStmt->bind_param('i', $assignment_id);
            $unitsStmt->execute();
            $unitsResult = $unitsStmt->get_result();
            
            $contentCount = 0;
            while ($unitRow = $unitsResult->fetch_assoc()) {
                // STEP 3: Delete content for each unit
                $contentStmt = $this->db->prepare("
                    UPDATE wp_training_program_content_assigned 
                    SET is_active = 0 
                    WHERE assigned_unit_id = ?
                ");
                
                if ($contentStmt) {
                    $contentStmt->bind_param('i', $unitRow['id']);
                    $contentStmt->execute();
                    $contentCount += $contentStmt->affected_rows;
                } else {
                    error_log("Phase 2C: Failed to prepare content cleanup query: " . $this->db->error);
                }
            }
            
            // STEP 4: Delete units
            $unitDeleteStmt = $this->db->prepare("
                UPDATE wp_training_program_units_assigned 
                SET is_active = 0 
                WHERE assignment_id = ?
            ");
            
            if ($unitDeleteStmt) {
                $unitDeleteStmt->bind_param('i', $assignment_id);
                $unitDeleteStmt->execute();
                $unitCount = $unitDeleteStmt->affected_rows;
            } else {
                error_log("Phase 2C: Failed to prepare unit cleanup query: " . $this->db->error);
                $unitCount = 0;
            }
            
            // STEP 5: Delete program
            $programDeleteStmt = $this->db->prepare("
                UPDATE wp_training_program_assigned 
                SET is_active = 0 
                WHERE id = ?
            ");
            
            if ($programDeleteStmt) {
                $programDeleteStmt->bind_param('i', $assigned_program_id);
                $programDeleteStmt->execute();
            } else {
                error_log("Phase 2C: Failed to prepare program cleanup query: " . $this->db->error);
            }
            
            error_log("Phase 2C: Snapshot cleanup completed - deleted 1 program, $unitCount units, $contentCount content items");
        } else {
            error_log("Phase 2C: No snapshot records found for assignment ID: $assignment_id (this is normal for old assignments)");
        }
        
    } catch (Exception $e) {
        error_log("Phase 2C: cleanupAssignmentSnapshot error: " . $e->getMessage());
        // Don't throw - this is cleanup, main operation should still succeed
    }
}

/**
 * CREDIT MANAGEMENT METHODS
 */
public function debugCredits() {
    try {
        $debug = [];
        
        // Check if table exists
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'wp_credit_to'");
        $debug['table_exists'] = $tableCheck->num_rows > 0;
        
        // Check directories
        $debug['directories'] = [
            'creditIconsDir_property' => isset($this->creditIconsDir) ? $this->creditIconsDir : 'NOT SET',
            'creditIconsUrl_property' => isset($this->creditIconsUrl) ? $this->creditIconsUrl : 'NOT SET',
            'creditIconsDir_exists' => isset($this->creditIconsDir) ? file_exists($this->creditIconsDir) : false,
            'creditIconsDir_writable' => isset($this->creditIconsDir) ? is_writable($this->creditIconsDir) : false,
        ];
        
        // Check if constructor methods were called
        $debug['constructor_check'] = [
            'setupTrainingContentDirectories_called' => isset($this->trainingContentDir),
            'setupCreditIconDirectories_called' => isset($this->creditIconsDir),
        ];
        
        // Check database connection
        $debug['database'] = [
            'connected' => $this->db->ping(),
            'error' => $this->db->error
        ];
        
        // Try to call setupCreditIconDirectories manually
        try {
            $setupResult = $this->setupCreditIconDirectories();
            $debug['manual_setup'] = [
                'result' => $setupResult,
                'creditIconsDir_after' => $this->creditIconsDir ?? 'STILL NOT SET',
                'creditIconsUrl_after' => $this->creditIconsUrl ?? 'STILL NOT SET'
            ];
        } catch (Exception $e) {
            $debug['manual_setup_error'] = $e->getMessage();
        }
        
        // Check if credits table has records
        if ($debug['table_exists']) {
            $countResult = $this->db->query("SELECT COUNT(*) as count FROM wp_credit_to");
            $debug['table_record_count'] = $countResult ? $countResult->fetch_assoc()['count'] : 'ERROR';
        }
        
        $this->sendSuccess($debug);
        
    } catch (Exception $e) {
        $this->sendError('Debug failed: ' . $e->getMessage(), 500);
    }
}

private function setupCreditIconDirectories() {
    // Use WordPress uploads directory structure
    if (defined('WP_CONTENT_DIR') && defined('WP_CONTENT_URL')) {
        $wpUploadsDir = WP_CONTENT_DIR . '/uploads';
        $wpUploadsUrl = WP_CONTENT_URL . '/uploads';
    } else {
        // Fallback if WordPress constants are not available
        $wpUploadsDir = dirname(__FILE__) . '/wp-content/uploads';
        $wpUploadsUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content/uploads';
    }
    
    // Create credit-icons specific directories
    $this->creditIconsDir = $wpUploadsDir . '/credit-icons';
    $this->creditIconsUrl = $wpUploadsUrl . '/credit-icons';
    
    // Create main credit-icons directory
    if (!is_dir($this->creditIconsDir)) {
        if (!$this->createDirectory($this->creditIconsDir)) {
            error_log("Failed to create credit icons directory: " . $this->creditIconsDir);
            return false;
        }
        error_log("Created credit icons directory: " . $this->creditIconsDir);
    }
    
    // Create thumbnails subdirectory
    $thumbnailsDir = $this->creditIconsDir . '/thumbnails';
    if (!is_dir($thumbnailsDir)) {
        if (!$this->createDirectory($thumbnailsDir)) {
            error_log("Failed to create credit icons thumbnails directory: $thumbnailsDir");
            return false;
        }
        error_log("Created credit icons thumbnails directory: $thumbnailsDir");
    }
    
    // Set proper permissions
    chmod($this->creditIconsDir, 0755);
    chmod($thumbnailsDir, 0755);
    
    // Verify directories are writable
    if (!is_writable($this->creditIconsDir)) {
        error_log("Credit icons directory is not writable: " . $this->creditIconsDir);
        // Try to fix permissions
        chmod($this->creditIconsDir, 0777);
        if (!is_writable($this->creditIconsDir)) {
            error_log("Failed to make credit icons directory writable after chmod 777");
            return false;
        }
    }
    
    if (!is_writable($thumbnailsDir)) {
        error_log("Credit icons thumbnails directory is not writable: $thumbnailsDir");
        // Try to fix permissions
        chmod($thumbnailsDir, 0777);
        if (!is_writable($thumbnailsDir)) {
            error_log("Failed to make credit icons thumbnails directory writable after chmod 777");
            return false;
        }
    }
    
    // Create .htaccess file for security
    $htaccessPath = $this->creditIconsDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "# Prevent PHP execution in uploads\n";
        $htaccessContent .= "<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">\n";
        $htaccessContent .= "    Order Allow,Deny\n";
        $htaccessContent .= "    Deny from all\n";
        $htaccessContent .= "</FilesMatch>\n";
        $htaccessContent .= "\n# Allow image files\n";
        $htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|svg)$\">\n";
        $htaccessContent .= "    Order Allow,Deny\n";
        $htaccessContent .= "    Allow from all\n";
        $htaccessContent .= "</FilesMatch>\n";
        
        file_put_contents($htaccessPath, $htaccessContent);
        chmod($htaccessPath, 0644);
    }
    
    error_log("Credit icons setup complete - Dir: " . $this->creditIconsDir . ", URL: " . $this->creditIconsUrl);
    error_log("Directory writable: " . (is_writable($this->creditIconsDir) ? 'YES' : 'NO'));
    error_log("Thumbnails writable: " . (is_writable($thumbnailsDir) ? 'YES' : 'NO'));
    
    return true;
}



/**
 * ENHANCED getCredits with icon support
 */
public function getCredits($params = []) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                c.id, 
                c.organization_name, 
                c.website_url, 
                c.description, 
                c.icon_url,
                c.icon_filename,
                c.icon_file_size,
                c.icon_mime_type,
                c.created_at, 
                c.updated_at,
                u.display_name as created_by_name
            FROM wp_credit_to c 
            LEFT JOIN wp_drill_users u ON c.created_by = u.id
            WHERE c.is_active = 1
            ORDER BY c.organization_name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $credits = [];
        while ($row = $result->fetch_assoc()) {
            // Add formatted file size for display
            if ($row['icon_file_size']) {
                $row['icon_file_size_formatted'] = $this->formatFileSize($row['icon_file_size']);
            }
            $credits[] = $row;
        }
        
        $this->sendSuccess($credits);
    } catch (Exception $e) {
        error_log("getCredits error: " . $e->getMessage());
        $this->sendError('Failed to fetch credits: ' . $e->getMessage(), 500);
    }
}


/**
 * ENHANCED getCredit with icon support
 */
public function getCredit($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT 
                c.id, 
                c.organization_name, 
                c.website_url, 
                c.description, 
                c.icon_url,
                c.icon_filename,
                c.icon_file_size,
                c.icon_mime_type,
                c.created_at, 
                c.updated_at,
                u.display_name as created_by_name
            FROM wp_credit_to c 
            LEFT JOIN wp_drill_users u ON c.created_by = u.id
            WHERE c.id = ? AND c.is_active = 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $credit = $result->fetch_assoc();
        
        if ($credit) {
            // Add formatted file size for display
            if ($credit['icon_file_size']) {
                $credit['icon_file_size_formatted'] = $this->formatFileSize($credit['icon_file_size']);
            }
            $this->sendSuccess($credit);
        } else {
            $this->sendError('Credit not found', 404);
        }
    } catch (Exception $e) {
        error_log("getCredit error: " . $e->getMessage());
        $this->sendError('Failed to fetch credit: ' . $e->getMessage(), 500);
    }
}


/**
 * ENHANCED createCredit with icon upload support
 */
public function createCredit($data) {
    try {
        error_log("createCredit called with icon support");
        
        // Handle both JSON and form data
        $inputData = array();
        $files = $_FILES;
        
        // Check content type to determine how to parse input
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // Handle JSON input (no file upload)
            $jsonInput = file_get_contents('php://input');
            $inputData = json_decode($jsonInput, true);
        } else {
            // Handle form data (with potential file upload)
            $inputData = $_POST;
        }
        
        $organization_name = isset($inputData['organization_name']) ? trim($inputData['organization_name']) : '';
        $website_url = isset($inputData['website_url']) ? trim($inputData['website_url']) : '';
        $description = isset($inputData['description']) ? trim($inputData['description']) : '';
        
        error_log("Processing credit: name='$organization_name', url='$website_url'");
        
        // Validation
        if (empty($organization_name)) {
            $this->sendError('Organization name is required', 400);
            return;
        }
        
        // Validate URL if provided
        if (!empty($website_url) && !filter_var($website_url, FILTER_VALIDATE_URL)) {
            $this->sendError('Invalid website URL format', 400);
            return;
        }
        
        // Prepare base data
        $website_url = !empty($website_url) ? $website_url : null;
        $description = !empty($description) ? $description : null;
        $created_by = 1; // Default to admin user ID 1
        
        // Initialize icon variables
        $icon_filename = null;
        $icon_url = null;
        $icon_file_size = null;
        $icon_mime_type = null;
        
        // Check if icon was uploaded
        if (isset($files['icon']) && $files['icon']['error'] === UPLOAD_ERR_OK) {
            error_log("Icon file uploaded, processing...");
            
            $iconFile = $files['icon'];
            
            // Setup icon directories
            $this->setupCreditIconDirectories();
            
            // Validate icon file type (only images)
            $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml');
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $iconFile['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $this->sendError('Invalid icon file type. Only JPG, PNG, GIF, WebP, and SVG are allowed', 400);
                return;
            }
            
            // Validate file size (2MB limit for icons)
            if ($iconFile['size'] > 2 * 1024 * 1024) {
                $this->sendError('Icon file size must be less than 2MB', 400);
                return;
            }
            
            // Store icon info for database
            $icon_filename = $iconFile['name'];
            $icon_file_size = $iconFile['size'];
            $icon_mime_type = $mimeType;
        }
        
        // Insert credit record first
        $stmt = $this->db->prepare("
            INSERT INTO wp_credit_to 
            (organization_name, website_url, description, created_by, icon_filename, icon_file_size, icon_mime_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('sssisss', $organization_name, $website_url, $description, $created_by, 
                         $icon_filename, $icon_file_size, $icon_mime_type);
        
        if (!$stmt->execute()) {
            $this->sendError('Failed to create credit: ' . $this->db->error, 500);
            return;
        }
        
        $creditId = $this->db->insert_id;
        error_log("Credit created with ID: $creditId");
        
        // Process icon file if uploaded
        if (isset($files['icon']) && $files['icon']['error'] === UPLOAD_ERR_OK) {
            $iconFile = $files['icon'];
            
            // Generate filename based on credit ID
            $extension = $this->getIconExtensionFromMime($icon_mime_type);
            $iconFilename = 'credit_icon_' . $creditId . '.' . $extension;
            $iconPath = $this->creditIconsDir . '/' . $iconFilename;
            
            // Move uploaded file
            if (!move_uploaded_file($iconFile['tmp_name'], $iconPath)) {
                // If file move fails, clean up the database record
                $this->db->query("DELETE FROM wp_credit_to WHERE id = $creditId");
                error_log("Failed to move icon file, cleaned up database record");
                $this->sendError('Failed to save icon file', 500);
                return;
            }
            
            // Update record with icon URL
            $icon_url = $this->creditIconsUrl . '/' . $iconFilename;
            $updateStmt = $this->db->prepare("UPDATE wp_credit_to SET icon_url = ? WHERE id = ?");
            $updateStmt->bind_param('si', $icon_url, $creditId);
            $updateStmt->execute();
            
            error_log("Icon saved successfully: $icon_url");
        }
        
        $this->sendSuccess([
            'id' => $creditId,
            'message' => 'Credit created successfully',
            'icon_uploaded' => !empty($icon_url),
            'icon_url' => $icon_url
        ]);
        
    } catch (Exception $e) {
        error_log("createCredit error: " . $e->getMessage());
        $this->sendError('Failed to create credit: ' . $e->getMessage(), 500);
    }
}

/**
 * ENHANCED updateCredit with icon upload support
 */
public function updateCredit($id, $data) {
    try {
        error_log("=== DEBUG updateCredit START ===");
        error_log("updateCredit called with ID: $id");
        
        // Handle different request methods
        $putData = array();
        $files = array();
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            error_log("DEBUG: Processing PUT request");
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                error_log("DEBUG: Parsing multipart form data");
                $this->parseMultipartFormData($putData, $files);
                error_log("DEBUG: Multipart parsing complete");
            } else {
                error_log("DEBUG: Parsing non-multipart PUT data");
                $jsonInput = file_get_contents('php://input');
                if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                    $putData = json_decode($jsonInput, true);
                } else {
                    parse_str($jsonInput, $putData);
                }
            }
        } else {
            $putData = $_POST;
            $files = $_FILES;
        }
        
        error_log("DEBUG: Files received: " . print_r($files, true));
        
        $organization_name = isset($putData['organization_name']) ? trim($putData['organization_name']) : '';
        $website_url = isset($putData['website_url']) ? trim($putData['website_url']) : '';
        $description = isset($putData['description']) ? trim($putData['description']) : '';
        
        error_log("DEBUG: Form data - name='$organization_name', url='$website_url'");
        
        // Validation
        if (empty($organization_name)) {
            error_log("DEBUG: Validation failed - empty organization name");
            $this->sendError('Organization name is required', 400);
            return;
        }
        
        // Validate URL if provided
        if (!empty($website_url) && !filter_var($website_url, FILTER_VALIDATE_URL)) {
            error_log("DEBUG: Validation failed - invalid URL");
            $this->sendError('Invalid website URL format', 400);
            return;
        }
        
        error_log("DEBUG: Validation passed, checking if credit exists");
        
        // Check if credit exists
        $checkStmt = $this->db->prepare("
            SELECT id, organization_name, icon_url, icon_filename, icon_file_size, icon_mime_type
            FROM wp_credit_to 
            WHERE id = ? AND is_active = 1
        ");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            error_log("DEBUG: Credit not found in database");
            $this->sendError('Credit not found', 404);
            return;
        }
        
        $existingCredit = $checkResult->fetch_assoc();
        error_log("DEBUG: Found existing credit: " . $existingCredit['organization_name']);
        
        // Initialize update variables
        $icon_url = $existingCredit['icon_url'];
        $icon_filename = $existingCredit['icon_filename'];
        $icon_file_size = $existingCredit['icon_file_size'];
        $icon_mime_type = $existingCredit['icon_mime_type'];
        $iconUpdated = false;
        
        // Check if new icon was uploaded
        if (isset($files['icon']) && $files['icon']['error'] === UPLOAD_ERR_OK) {
            error_log("DEBUG: Icon file detected, starting processing...");
            
            // Setup directories first
            error_log("DEBUG: Calling setupCreditIconDirectories...");
            $dirSetup = $this->setupCreditIconDirectories();
            if (!$dirSetup) {
                error_log("DEBUG: setupCreditIconDirectories failed");
                $this->sendError('Failed to setup icon directories', 500);
                return;
            }
            error_log("DEBUG: setupCreditIconDirectories completed");
            
            $file = $files['icon'];
            $uploadDir = $this->creditIconsDir;
            
            // Enhanced debugging
            error_log("DEBUG: Upload directory: $uploadDir");
            error_log("DEBUG: Directory exists: " . (file_exists($uploadDir) ? 'YES' : 'NO'));
            error_log("DEBUG: Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
            error_log("DEBUG: Current user: " . get_current_user());
            error_log("DEBUG: Directory permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
            
            // Validate file
            error_log("DEBUG: File validation - type: {$file['type']}, size: {$file['size']}");
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            if (!in_array($file['type'], $allowedTypes)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images are allowed.']);
                return;
            }
            
            if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB']);
                return;
            }
            
            // Get file extension
            error_log("DEBUG: Getting file extension...");
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Generate unique filename
            $filename = 'credit_icon_' . $id . '.' . $extension;
            $targetPath = $uploadDir . '/' . $filename;
            
            error_log("DEBUG: Target path: $targetPath");
            
            // Check if target directory is writable one more time
            if (!is_writable($uploadDir)) {
                error_log("ERROR: Upload directory not writable: $uploadDir");
                // Try to fix permissions as last resort
                chmod($uploadDir, 0777);
                if (!is_writable($uploadDir)) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Upload directory not writable']);
                    return;
                }
            }
            
            // Move file with enhanced error reporting
            error_log("DEBUG: Moving uploaded file from {$file['tmp_name']} to $targetPath");
            
            // Check if tmp file exists
            if (!file_exists($file['tmp_name'])) {
                error_log("ERROR: Temporary file does not exist: {$file['tmp_name']}");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Temporary file not found']);
                return;
            }
            
            // Check if tmp file is readable
            if (!is_readable($file['tmp_name'])) {
                error_log("ERROR: Temporary file not readable: {$file['tmp_name']}");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Temporary file not readable']);
                return;
            }
            
            // Delete old icon file if it exists
            if (!empty($existingCredit['icon_filename'])) {
                $oldIconPath = $uploadDir . '/' . $existingCredit['icon_filename'];
                if (file_exists($oldIconPath)) {
                    unlink($oldIconPath);
                    error_log("DEBUG: Deleted old icon file: $oldIconPath");
                }
            }
            
            // Try the move
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                error_log("DEBUG: File moved successfully to $targetPath");
                
                // Set proper permissions on the uploaded file
                chmod($targetPath, 0644);
                
                // Verify file was actually created
                if (!file_exists($targetPath)) {
                    error_log("ERROR: File was 'moved' but doesn't exist at target path: $targetPath");
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'File upload verification failed']);
                    return;
                }
                
                // Update icon variables for database update
                $icon_filename = $filename;
                $icon_url = $this->creditIconsUrl . '/' . $filename;
                $icon_file_size = $file['size'];
                $icon_mime_type = $file['type'];
                $iconUpdated = true;
                
                error_log("DEBUG: Icon upload successful - filename: $icon_filename, url: $icon_url");
                
            } else {
                // Enhanced error reporting for move failure
                $error = error_get_last();
                error_log("ERROR: Failed to move uploaded file. Last error: " . print_r($error, true));
                error_log("ERROR: Source: {$file['tmp_name']}, Target: $targetPath");
                error_log("ERROR: Source exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO'));
                error_log("ERROR: Source readable: " . (is_readable($file['tmp_name']) ? 'YES' : 'NO'));
                error_log("ERROR: Target dir exists: " . (file_exists($uploadDir) ? 'YES' : 'NO'));
                error_log("ERROR: Target dir writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
                
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to upload icon file']);
                return;
            }
        }
        
        // Prepare values for update
        $website_url_value = !empty($website_url) ? $website_url : null;
        $description_value = !empty($description) ? $description : null;
        
        error_log("DEBUG: Preparing database update, iconUpdated: " . ($iconUpdated ? 'true' : 'false'));
        
        // Update the credit record
        if ($iconUpdated) {
            error_log("DEBUG: Updating with new icon");
            $stmt = $this->db->prepare("
                UPDATE wp_credit_to 
                SET organization_name = ?, website_url = ?, description = ?, 
                    icon_url = ?, icon_filename = ?, icon_file_size = ?, icon_mime_type = ?, 
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            if (!$stmt) {
                error_log("DEBUG: Prepare failed: " . $this->db->error);
                throw new Exception('Update prepare failed: ' . $this->db->error);
            }
            
            error_log("DEBUG: Binding parameters for icon update");
            $stmt->bind_param('sssssssi', $organization_name, $website_url_value, $description_value, 
                             $icon_url, $icon_filename, $icon_file_size, $icon_mime_type, $id);
        } else {
            error_log("DEBUG: Updating without icon changes");
            $stmt = $this->db->prepare("
                UPDATE wp_credit_to 
                SET organization_name = ?, website_url = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if (!$stmt) {
                error_log("DEBUG: Prepare failed: " . $this->db->error);
                throw new Exception('Update prepare failed: ' . $this->db->error);
            }
            
            error_log("DEBUG: Binding parameters for regular update");
            $stmt->bind_param('sssi', $organization_name, $website_url_value, $description_value, $id);
        }
        
        error_log("DEBUG: Executing update query");
        if ($stmt->execute()) {
            error_log("DEBUG: Update successful - affected rows: " . $stmt->affected_rows);
            $this->sendSuccess([
                'message' => 'Credit updated successfully',
                'icon_updated' => $iconUpdated,
                'icon_url' => $icon_url
            ]);
        } else {
            error_log("DEBUG: Update execution failed: " . $this->db->error);
            throw new Exception('Update execution failed: ' . $this->db->error);
        }
        
        error_log("=== DEBUG updateCredit END ===");
        
    } catch (Exception $e) {
        error_log("DEBUG: Exception caught: " . $e->getMessage());
        error_log("DEBUG: Stack trace: " . $e->getTraceAsString());
        $this->sendError('Failed to update credit: ' . $e->getMessage(), 500);
    }
}
/**
 * ADD this helper method to your DrillAPI class
 */
private function getIconExtensionFromMime($mimeType) {
    $mimeToExt = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg'
    );
    
    return isset($mimeToExt[$mimeType]) ? $mimeToExt[$mimeType] : 'png';
}

/**
 * ADD this helper method to your DrillAPI class
 */
private function cleanupCreditIconFiles($iconUrl) {
    if ($iconUrl) {
        $oldIconPath = str_replace($this->creditIconsUrl, $this->creditIconsDir, $iconUrl);
        if (file_exists($oldIconPath)) {
            unlink($oldIconPath);
            error_log("Removed old credit icon: $oldIconPath");
        }
    }
}
/**
 * ENHANCED deleteCredit with icon cleanup
 */
public function deleteCredit($id) {
    try {
        // Get credit info before deletion for cleanup
        $stmt = $this->db->prepare("
            SELECT id, organization_name, icon_url 
            FROM wp_credit_to 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Credit not found', 404);
            return;
        }
        
        $credit = $result->fetch_assoc();
        
        // Clean up icon file if it exists
        if ($credit['icon_url']) {
            $this->cleanupCreditIconFiles($credit['icon_url']);
        }
        
        // Soft delete by setting is_active to 0
        $deleteStmt = $this->db->prepare("UPDATE wp_credit_to SET is_active = 0, updated_at = NOW() WHERE id = ?");
        $deleteStmt->bind_param('i', $id);
        
        if ($deleteStmt->execute()) {
            $this->sendSuccess(['message' => 'Credit deleted successfully']);
        } else {
            $this->sendError('Failed to delete credit', 500);
        }
    } catch (Exception $e) {
        error_log("deleteCredit error: " . $e->getMessage());
        $this->sendError('Failed to delete credit: ' . $e->getMessage(), 500);
    }
}


/**
 * Toggle lock status for an assigned unit (UPDATE ONLY - NO TABLE CREATION)
 */
public function toggleUnitLock($params = []) {
    try {
        $assigned_unit_id = $params['assigned_unit_id'] ?? 0;
        $is_locked = isset($params['is_locked']) ? intval($params['is_locked']) : null;
        $coach_id = $params['coach_id'] ?? 0;
        
        if (!$assigned_unit_id) {
            $this->sendError('Assigned unit ID is required', 400);
            return;
        }
        
        if ($is_locked === null) {
            $this->sendError('Lock status (is_locked) is required', 400);
            return;
        }
        
        if (!$coach_id) {
            $this->sendError('Coach authentication required', 401);
            return;
        }
        
        // Verify the assigned unit exists and get assignment info
        $checkStmt = $this->db->prepare("
            SELECT ua.id, ua.name, ua.is_locked, ua.assignment_id,
                   tpa.assigned_by, u.display_name as student_name
            FROM wp_training_program_units_assigned ua
            JOIN wp_training_program_assignments tpa ON ua.assignment_id = tpa.id
            JOIN wp_drill_users u ON tpa.user_id = u.id
            WHERE ua.id = ? AND ua.is_active = 1
        ");
        
        if (!$checkStmt) {
            throw new Exception('Check prepare failed: ' . $this->db->error);
        }
        
        $checkStmt->bind_param('i', $assigned_unit_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Assigned unit not found', 404);
            return;
        }
        
        $unit = $result->fetch_assoc();
        
        // Verify the coach has permission (must be the assigning coach)
        if ($unit['assigned_by'] != $coach_id) {
            $this->sendError('Access denied: You can only manage units for assignments you created', 403);
            return;
        }
        
        // Check if the status is actually changing
        if ($unit['is_locked'] == $is_locked) {
            $status = $is_locked ? 'locked' : 'unlocked';
            $this->sendSuccess([
                'message' => "Unit '{$unit['name']}' is already $status",
                'unit_name' => $unit['name'],
                'student_name' => $unit['student_name'],
                'is_locked' => (bool)$is_locked,
                'changed' => false
            ]);
            return;
        }
        
        // UPDATE EXISTING RECORD ONLY - NO TABLE CREATION
        $updateStmt = $this->db->prepare("
            UPDATE wp_training_program_units_assigned 
            SET is_locked = ?, 
                unlocked_by = CASE WHEN ? = 0 THEN ? ELSE unlocked_by END,
                unlocked_date = CASE WHEN ? = 0 THEN NOW() ELSE unlocked_date END,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$updateStmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $updateStmt->bind_param('iiiii', $is_locked, $is_locked, $coach_id, $is_locked, $assigned_unit_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update unit lock status: ' . $this->db->error);
        }
        
        if ($updateStmt->affected_rows === 0) {
            $this->sendError('No changes made to unit lock status', 400);
            return;
        }
        
        $action = $is_locked ? 'locked' : 'unlocked';
        $lockIcon = $is_locked ? '🔒' : '🔓';
        
        error_log("Unit lock status changed: Unit ID $assigned_unit_id $action by coach $coach_id");
        
        $this->sendSuccess([
            'message' => "$lockIcon Unit '{$unit['name']}' has been $action for {$unit['student_name']}",
            'unit_name' => $unit['name'],
            'student_name' => $unit['student_name'],
            'is_locked' => (bool)$is_locked,
            'changed' => true,
            'action' => $action
        ]);
        
    } catch (Exception $e) {
        error_log("toggleUnitLock error: " . $e->getMessage());
        $this->sendError('Failed to update unit lock status: ' . $e->getMessage(), 500);
    }
}

// Also add this new method to check if tables exist (for graceful degradation)
/**
 * NEW METHOD: Check if snapshot tables exist
 */
private function snapshotTablesExist() {
    try {
        $tables = [
            'wp_training_program_assigned',
            'wp_training_program_units_assigned', 
            'wp_training_program_content_assigned'
        ];
        
        foreach ($tables as $table) {
            $result = $this->db->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                error_log("Phase 2C: Snapshot table '$table' does not exist");
                return false;
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Phase 2C: Error checking snapshot tables: " . $e->getMessage());
        return false;
    }
}
/**
 * Get the next assignment sequence number for a user/program combination
 */
public function getNextAssignmentSequence($params) {
    try {
        $user_id = $params['user_id'] ?? null;
        $program_id = $params['program_id'] ?? null;
        
        if (!$user_id || !$program_id) {
            $this->sendError('user_id and program_id are required', 400);
            return;
        }
        
        // Validate that user and program exist
        $userCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $userCheck->bind_param('i', $user_id);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid user ID', 400);
            return;
        }
        
        $programCheck = $this->db->prepare("SELECT id FROM wp_training_programs WHERE id = ? AND is_active = 1");
        $programCheck->bind_param('i', $program_id);
        $programCheck->execute();
        if ($programCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid program ID', 400);
            return;
        }
        
        $next_sequence = $this->getNextSequenceNumber($user_id, $program_id);
        
        $this->sendSuccess([
            'next_sequence' => $next_sequence,
            'user_id' => (int)$user_id,
            'program_id' => (int)$program_id
        ]);
        
    } catch (Exception $e) {
        error_log("getNextAssignmentSequence error: " . $e->getMessage());
        $this->sendError('Failed to get next sequence number: ' . $e->getMessage(), 500);
    }
}

/**
 * PERMANENT FIX: Replace your getAssignmentHistory method with this complete version
 */

public function getAssignmentHistory($params) {
    try {
        $user_id = $params['user_id'] ?? null;
        $program_id = $params['program_id'] ?? null;
        
        if (!$user_id || !$program_id) {
            $this->sendError('user_id and program_id are required', 400);
            return;
        }
        
        // Validate that user and program exist
        $userCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $userCheck->bind_param('i', $user_id);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid user ID', 400);
            return;
        }
        
        $programCheck = $this->db->prepare("SELECT id FROM wp_training_programs WHERE id = ? AND is_active = 1");
        $programCheck->bind_param('i', $program_id);
        $programCheck->execute();
        if ($programCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid program ID', 400);
            return;
        }
        
        // Get assignment history with all related data
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   p.name as program_name,
                   p.description as program_description,
                   p.difficulty_level,
                   u.display_name as student_name,
                   u.email as student_email,
                   COALESCE(coach.display_name, 'Unknown') as assigned_by_name,
                   
                   -- Check if this assignment has snapshot data
                   (SELECT COUNT(*) FROM wp_training_program_assigned tpa_snap 
                    WHERE tpa_snap.assignment_id = a.id AND tpa_snap.is_active = 1) as has_snapshot
                   
            FROM wp_training_program_assignments a
            JOIN wp_training_programs p ON a.program_id = p.id
            JOIN wp_drill_users u ON a.user_id = u.id
            LEFT JOIN wp_drill_users coach ON a.assigned_by = coach.id AND coach.is_active = 1
            WHERE a.user_id = ? AND a.program_id = ?
            ORDER BY a.assignment_sequence DESC
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('ii', $user_id, $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            // Add computed fields for better frontend handling
            $row['has_snapshot'] = (bool)$row['has_snapshot'];
            $row['progress_percentage'] = (float)$row['progress_percentage'];
            $row['assignment_sequence'] = (int)$row['assignment_sequence'];
            $row['is_active'] = (bool)$row['is_active'];
            
            // Format dates for better display
            if ($row['assigned_date']) {
                $row['assigned_date_formatted'] = date('M j, Y g:i A', strtotime($row['assigned_date']));
            }
            if ($row['completion_date']) {
                $row['completion_date_formatted'] = date('M j, Y g:i A', strtotime($row['completion_date']));
            }
            
            $history[] = $row;
        }
        
        error_log("getAssignmentHistory: Found " . count($history) . " assignments for user $user_id, program $program_id");
        
        $this->sendSuccess($history);
        
    } catch (Exception $e) {
        error_log("getAssignmentHistory error: " . $e->getMessage());
        $this->sendError('Failed to get assignment history: ' . $e->getMessage(), 500);
    }
}


/**
 * Helper method to get next sequence number
 */
private function getNextSequenceNumber($user_id, $program_id) {
    try {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(assignment_sequence), 0) + 1 as next_sequence 
            FROM wp_training_program_assignments 
            WHERE user_id = ? AND program_id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Sequence query prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('ii', $user_id, $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return (int)$row['next_sequence'];
        }
        
        return 1; // Default to sequence 1 if no records found
        
    } catch (Exception $e) {
        error_log("getNextSequenceNumber error: " . $e->getMessage());
        return 1; // Default to sequence 1 on error
    }
}

/**
 * Get all content for a specific unit (convenience endpoint)
 */
public function getUnitContent($params = []) {
    try {
        $unit_id = $params['unit_id'] ?? 0;
        
        if (!$unit_id) {
            $this->sendError('Unit ID is required', 400);
            return;
        }
        
        // Use the existing method with unit_id filter
        $this->getTrainingProgramContents(['unit_id' => $unit_id]);
        
    } catch (Exception $e) {
        error_log("getUnitContent error: " . $e->getMessage());
        $this->sendError('Failed to load unit content: ' . $e->getMessage(), 500);
    }
}

/**
 * Setup training content upload directories
 */
private function setupTrainingContentDirectories() {
    // Use WordPress uploads directory structure
    if (defined('WP_CONTENT_DIR') && defined('WP_CONTENT_URL')) {
        $wpUploadsDir = WP_CONTENT_DIR . '/uploads';
        $wpUploadsUrl = WP_CONTENT_URL . '/uploads';
    } else {
        // Fallback if WordPress constants are not available
        $wpUploadsDir = dirname(__FILE__) . '/wp-content/uploads';
        $wpUploadsUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content/uploads';
    }
    
    // Create training content specific directories
    $this->trainingContentDir = $wpUploadsDir . '/training-content';
    $this->trainingContentUrl = $wpUploadsUrl . '/training-content';
    
    // Create directories if they don't exist
    if (!is_dir($this->trainingContentDir)) {
        $this->createDirectory($this->trainingContentDir);
    }
    
    // Create thumbnails subdirectory for image content
    $thumbnailsDir = $this->trainingContentDir . '/thumbnails';
    if (!is_dir($thumbnailsDir)) {
        $this->createDirectory($thumbnailsDir);
    }
    
    error_log("Training content setup complete - Dir: " . $this->trainingContentDir . ", URL: " . $this->trainingContentUrl);
}
/**
 * Create training content table if it doesn't exist
 */

private function createTrainingContentTable() {
    $sql = "CREATE TABLE IF NOT EXISTS wp_training_content (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        content_type enum('image','pdf','document','video','link','other') DEFAULT 'document',
        category_id int(11) NOT NULL,
        skill_id int(11) NOT NULL,
        difficulty_level enum('beginner','intermediate','advanced') DEFAULT 'beginner',
        original_filename varchar(255) DEFAULT NULL,
        file_size bigint DEFAULT NULL,
        mime_type varchar(100) DEFAULT NULL,
        file_url varchar(500) DEFAULT NULL,
        external_url varchar(500) DEFAULT NULL,
        thumbnail_url varchar(500) DEFAULT NULL,
        visibility enum('public','private') DEFAULT 'private',
        download_count int(11) DEFAULT 0,
        created_by int(11) DEFAULT NULL,
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_content_active (is_active),
        KEY idx_content_name (name),
        KEY idx_content_type (content_type),
        KEY idx_content_category (category_id),
        KEY idx_content_skill (skill_id),
        KEY idx_content_difficulty (difficulty_level),
        KEY idx_content_visibility (visibility),
        KEY idx_content_external_url (external_url),
        KEY fk_content_created_by (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    if (!$this->db->query($sql)) {
        error_log("Failed to create wp_training_content table: " . $this->db->error);
        throw new Exception("Failed to create training content table: " . $this->db->error);
    }
    
    error_log("Training content table created/verified successfully");
}

/**
 * Get all training content with filtering
 */

public function getTrainingContents($params = array()) {
    try {
        $where = ["tc.is_active = 1"];
        $bindings = [];
        $types = "";
        
        // Filter by content type
        if (!empty($params['content_type'])) {
            $where[] = "tc.content_type = ?";
            $bindings[] = $params['content_type'];
            $types .= "s";
        }
        
        // Filter by category
        if (!empty($params['category_id'])) {
            $where[] = "tc.category_id = ?";
            $bindings[] = $params['category_id'];
            $types .= "i";
        }
        
        // Filter by skill
        if (!empty($params['skill_id'])) {
            $where[] = "tc.skill_id = ?";
            $bindings[] = $params['skill_id'];
            $types .= "i";
        }
        
        // Filter by difficulty level
        if (!empty($params['difficulty_level'])) {
            $where[] = "tc.difficulty_level = ?";
            $bindings[] = $params['difficulty_level'];
            $types .= "s";
        }
        
        // Filter by visibility
        if (!empty($params['visibility'])) {
            $where[] = "tc.visibility = ?";
            $bindings[] = $params['visibility'];
            $types .= "s";
        }
        
        // Filter by credit
        if (!empty($params['credit_id'])) {
            $where[] = "tc.credit_id = ?";
            $bindings[] = $params['credit_id'];
            $types .= "i";
        }
        
        // Search filter
        if (!empty($params['search'])) {
            $where[] = "(tc.name LIKE ? OR tc.description LIKE ?)";
            $searchTerm = '%' . $params['search'] . '%';
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $types .= "ss";
        }
        
        // FIXED: Updated SQL to return credit fields with correct names
        $sql = "SELECT tc.id, tc.name, tc.description, tc.content_type, tc.category_id, tc.skill_id,
                       tc.difficulty_level, tc.original_filename, tc.file_size, tc.mime_type,
                       tc.file_url, tc.external_url, tc.thumbnail_url, tc.visibility, tc.download_count,
                       tc.created_by, tc.created_at, tc.updated_at, tc.credit_id,
                       dc.display_name as category_display,
                       ds.display_name as skill_display,
                       u.display_name as created_by_name,
                       
                       -- FIXED: Return credit information with the field names the frontend expects
                       cr.organization_name as credit_to,
                       cr.website_url as credit_url,
                       cr.icon_url as credit_image_url,
                       cr.description as credit_description
                       
                FROM wp_training_content tc
                JOIN wp_drill_categories dc ON tc.category_id = dc.id
                JOIN wp_drill_skills ds ON tc.skill_id = ds.id
                LEFT JOIN wp_drill_users u ON tc.created_by = u.id
                LEFT JOIN wp_credit_to cr ON tc.credit_id = cr.id AND cr.is_active = 1
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tc.created_at DESC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
            if (!$result) {
                throw new Exception('Query failed: ' . $this->db->error);
            }
        }
        
        $contents = array();
        while ($row = $result->fetch_assoc()) {
            // Ensure created_by_name has a fallback
            if (!$row['created_by_name']) {
                $row['created_by_name'] = 'Unknown User';
            }
            
            // Format file size for display
            $row['file_size_formatted'] = $this->formatFileSize($row['file_size']);
            
            // FIXED: Log credit information for debugging
            if ($row['credit_id']) {
                error_log("Content '{$row['name']}' has credit_id: {$row['credit_id']}, credit_to: {$row['credit_to']}, credit_url: {$row['credit_url']}");
            }
            
            $contents[] = $row;
        }
        
        error_log("getTrainingContents returning " . count($contents) . " items with fixed credit fields");
        $this->sendSuccess($contents);
        
    } catch (Exception $e) {
        error_log("getTrainingContents error: " . $e->getMessage());
        $this->sendError('Failed to load training content: ' . $e->getMessage(), 500);
    }
}

/**
 * Get a specific training content item by ID
 */

public function getTrainingContent($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT tc.*, 
                   dc.display_name as category_display,
                   ds.display_name as skill_display,
                   u.display_name as created_by_name,
                   
                   -- FIXED: Return credit information with the field names the frontend expects
                   cr.organization_name as credit_to,
                   cr.website_url as credit_url,
                   cr.description as credit_description,
                   cr.icon_url as credit_image_url
                   
            FROM wp_training_content tc
            JOIN wp_drill_categories dc ON tc.category_id = dc.id
            JOIN wp_drill_skills ds ON tc.skill_id = ds.id
            LEFT JOIN wp_drill_users u ON tc.created_by = u.id
            LEFT JOIN wp_credit_to cr ON tc.credit_id = cr.id AND cr.is_active = 1
            WHERE tc.id = ? AND tc.is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($content = $result->fetch_assoc()) {
            // Ensure created_by_name has a fallback
            if (!$content['created_by_name']) {
                $content['created_by_name'] = 'Unknown User';
            }
            
            // Format file size for display
            $content['file_size_formatted'] = $this->formatFileSize($content['file_size']);
            
            // FIXED: Log credit information for debugging
            if ($content['credit_id']) {
                error_log("Single content '{$content['name']}' has credit_id: {$content['credit_id']}, credit_to: {$content['credit_to']}, credit_url: {$content['credit_url']}");
            }
            
            $this->sendSuccess($content);
        } else {
            $this->sendError('Training content not found', 404);
        }
        
    } catch (Exception $e) {
        error_log("getTrainingContent error: " . $e->getMessage());
        $this->sendError('Failed to load training content: ' . $e->getMessage(), 500);
    }
}


/**
 * Create new training content with file upload OR URL link
 */
public function createTrainingContent() {
    try {
        error_log("createTrainingContent called with credit support");
        
        // Handle both JSON and form data
        $inputData = array();
        $files = $_FILES;
        
        // Check content type to determine how to parse input
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // Handle JSON input (for URL links)
            $jsonInput = file_get_contents('php://input');
            $inputData = json_decode($jsonInput, true);
            error_log("Processing JSON input: " . $jsonInput);
        } else {
            // Handle form data (for file uploads)
            $inputData = $_POST;
            error_log("Processing form POST data: " . print_r($_POST, true));
        }
        
        error_log("Parsed input data: " . print_r($inputData, true));
        error_log("FILES data: " . print_r($files, true));
        
        $name = isset($inputData['name']) ? trim($inputData['name']) : '';
        $description = isset($inputData['description']) ? trim($inputData['description']) : '';
        $category_id = isset($inputData['category_id']) ? intval($inputData['category_id']) : 0;
        $skill_id = isset($inputData['skill_id']) ? intval($inputData['skill_id']) : 0;
        $difficulty_level = isset($inputData['difficulty_level']) ? $inputData['difficulty_level'] : 'beginner';
        $visibility = isset($inputData['visibility']) ? $inputData['visibility'] : 'private';
        $created_by = isset($inputData['created_by']) ? intval($inputData['created_by']) : null;
        $content_type = isset($inputData['content_type']) ? $inputData['content_type'] : 'file';
        $external_url = isset($inputData['external_url']) ? trim($inputData['external_url']) : '';
        $credit_id = isset($inputData['credit_id']) && !empty($inputData['credit_id']) ? intval($inputData['credit_id']) : null;
        
        error_log("Parsed data: name='$name', category=$category_id, skill=$skill_id, content_type='$content_type', credit_id=$credit_id");
        
        // Validation
        if (empty($name)) {
            error_log("Validation failed: Name is empty - received: '" . $name . "'");
            $this->sendError('Name is required', 400);
            return;
        }
        
        if (!$category_id || !$skill_id) {
            error_log("Validation failed: category=$category_id, skill=$skill_id");
            $this->sendError('Category and skill are required', 400);
            return;
        }
        
        if (!$created_by) {
            error_log("Validation failed: created_by=$created_by");
            $this->sendError('User authentication required', 401);
            return;
        }
        
        // Validate credit if provided
        if ($credit_id) {
            $creditCheck = $this->db->prepare("SELECT id FROM wp_credit_to WHERE id = ? AND is_active = 1");
            $creditCheck->bind_param('i', $credit_id);
            $creditCheck->execute();
            if ($creditCheck->get_result()->num_rows === 0) {
                $this->sendError('Invalid credit organization selected', 400);
                return;
            }
        }
        
        // Handle URL link content
        if ($content_type === 'link') {
            if (empty($external_url)) {
                $this->sendError('External URL is required for link content', 400);
                return;
            }
            
            // Validate URL format
            if (!filter_var($external_url, FILTER_VALIDATE_URL)) {
                $this->sendError('Invalid URL format', 400);
                return;
            }
            
            error_log("Processing URL link: $external_url");
            
            // Insert URL link record with credit support
            $stmt = $this->db->prepare("
                INSERT INTO wp_training_content 
                (name, description, content_type, category_id, skill_id, difficulty_level, 
                 external_url, visibility, created_by, credit_id) 
                VALUES (?, ?, 'link', ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception('Insert prepare failed: ' . $this->db->error);
            }
            
            $stmt->bind_param('ssiisssii', $name, $description, $category_id, $skill_id, 
                             $difficulty_level, $external_url, $visibility, $created_by, $credit_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert URL link: ' . $this->db->error);
            }
            
            $contentId = $this->db->insert_id;
            error_log("URL link saved successfully with ID: $contentId");
            
            $this->sendSuccess(array(
                'id' => $contentId, 
                'message' => 'URL link saved successfully',
                'content_type' => 'link',
                'external_url' => $external_url,
                'credit_assigned' => !empty($credit_id)
            ));
            return;
        }
        
        // Handle file upload content (existing code with credit support)
        if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . (isset($files['file']) ? $files['file']['error'] : 'no file'));
            $this->sendError('File is required for file upload', 400);
            return;
        }
        
        $uploadedFile = $files['file'];
        error_log("Processing uploaded file: " . $uploadedFile['name'] . " (" . $uploadedFile['size'] . " bytes)");
        
        // Setup directories
        $this->setupTrainingContentDirectories();
        $this->createCreditsTable();
        $this->setupCreditIconDirectories();
        
        // Validate file type and determine content type
        $allowedTypes = array(
            'application/pdf' => 'pdf',
            'application/msword' => 'document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'image/jpeg' => 'image',
            'image/png' => 'image',
            'image/gif' => 'image',
            'image/webp' => 'image',
            'video/mp4' => 'video',
            'video/webm' => 'video',
            'video/quicktime' => 'video'
        );
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, $allowedTypes)) {
            $this->sendError('Invalid file type. Allowed: PDF, Word documents, images, and videos', 400);
            return;
        }
        
        $fileContentType = $allowedTypes[$mimeType];
        
        // Validate file size (50MB limit)
        if ($uploadedFile['size'] > 50 * 1024 * 1024) {
            $this->sendError('File size must be less than 50MB', 400);
            return;
        }
        
        // Insert training content record first to get the ID (with credit support)
        $stmt = $this->db->prepare("
            INSERT INTO wp_training_content 
            (name, description, content_type, category_id, skill_id, difficulty_level, 
             original_filename, file_size, mime_type, visibility, created_by, credit_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Insert prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('sssiisssisii', $name, $description, $fileContentType, $category_id, $skill_id, 
                         $difficulty_level, $uploadedFile['name'], $uploadedFile['size'], 
                         $mimeType, $visibility, $created_by, $credit_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert training content record: ' . $this->db->error);
        }
        
        $contentId = $this->db->insert_id;
        error_log("Database insert successful. Content ID: $contentId");
        
        // Generate filename based on the content ID
        $extension = $this->getFileExtensionFromMime($mimeType);
        $filename = 'content_' . $contentId . '.' . $extension;
        $filepath = $this->trainingContentDir . '/' . $filename;
        
        error_log("Moving file to: $filepath");
        
        // Move uploaded file to final location
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filepath)) {
            // If file move fails, clean up the database record
            $this->db->query("DELETE FROM wp_training_content WHERE id = $contentId");
            error_log("Failed to move uploaded file, cleaned up database record");
            $this->sendError('Failed to save uploaded file', 500);
            return;
        }
        
        // Generate thumbnail for images
        $thumbnailUrl = null;
        if ($fileContentType === 'image') {
            $thumbnailFilename = 'thumb_content_' . $contentId . '.' . $extension;
            $thumbnailPath = $this->trainingContentDir . '/thumbnails/' . $thumbnailFilename;
            
            if ($this->generateThumbnailAtPath($filepath, $thumbnailPath, $mimeType)) {
                $thumbnailUrl = $this->trainingContentUrl . '/thumbnails/' . $thumbnailFilename;
            }
        }
        
        // Update the record with file URL
        $fileUrl = $this->trainingContentUrl . '/' . $filename;
        $updateStmt = $this->db->prepare("
            UPDATE wp_training_content 
            SET file_url = ?, thumbnail_url = ? 
            WHERE id = ?
        ");
        
        if (!$updateStmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $updateStmt->bind_param('ssi', $fileUrl, $thumbnailUrl, $contentId);
        
        if (!$updateStmt->execute()) {
            error_log("Failed to update URLs in database: " . $this->db->error);
        }
        
        error_log("Training content saved successfully. File URL: $fileUrl");
        
        $this->sendSuccess(array(
            'id' => $contentId, 
            'message' => 'Training content uploaded successfully',
            'content_type' => $fileContentType,
            'file_url' => $fileUrl,
            'thumbnail_url' => $thumbnailUrl,
            'filename' => $filename,
            'credit_assigned' => !empty($credit_id)
        ));
        
    } catch (Exception $e) {
        error_log("createTrainingContent error: " . $e->getMessage());
        $this->sendError('Failed to create training content: ' . $e->getMessage(), 500);
    }
}

/**
 * Update existing training content
 */

public function updateTrainingContent($id) {
    try {
        error_log("updateTrainingContent called with ID: $id (with credit support)");
        
        // Handle different request methods
        $putData = array();
        $files = array();
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                $this->parseMultipartFormData($putData, $files);
            } else {
                parse_str(file_get_contents('php://input'), $putData);
            }
        } else {
            $putData = $_POST;
            $files = $_FILES;
        }
        
        $name = isset($putData['name']) ? trim($putData['name']) : '';
        $description = isset($putData['description']) ? trim($putData['description']) : '';
        $category_id = isset($putData['category_id']) ? intval($putData['category_id']) : 0;
        $skill_id = isset($putData['skill_id']) ? intval($putData['skill_id']) : 0;
        $difficulty_level = isset($putData['difficulty_level']) ? $putData['difficulty_level'] : 'beginner';
        $visibility = isset($putData['visibility']) ? $putData['visibility'] : 'private';
        $content_type = isset($putData['content_type']) ? $putData['content_type'] : 'file';
        $external_url = isset($putData['external_url']) ? trim($putData['external_url']) : '';
        $credit_id = isset($putData['credit_id']) && !empty($putData['credit_id']) ? intval($putData['credit_id']) : null;

        // Validate credit if provided
        if ($credit_id) {
            $creditCheck = $this->db->prepare("SELECT id FROM wp_credit_to WHERE id = ? AND is_active = 1");
            $creditCheck->bind_param('i', $credit_id);
            $creditCheck->execute();
            if ($creditCheck->get_result()->num_rows === 0) {
                $this->sendError('Invalid credit organization selected', 400);
                return;
            }
        }

        // Handle URL link updates
        if ($content_type === 'link') {
            if (empty($external_url)) {
                $this->sendError('External URL is required for link content', 400);
                return;
            }
            
            if (!filter_var($external_url, FILTER_VALIDATE_URL)) {
                $this->sendError('Invalid URL format', 400);
                return;
            }
            
            // Update with URL link and credit
            $updateStmt = $this->db->prepare("
                UPDATE wp_training_content SET 
                name = ?, description = ?, category_id = ?, skill_id = ?, 
                difficulty_level = ?, visibility = ?, content_type = 'link',
                file_url = ?, credit_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->bind_param('ssiisssii', $name, $description, $category_id, $skill_id, 
                                   $difficulty_level, $visibility, $external_url, $credit_id, $id);
            
            if ($updateStmt->execute()) {
                $this->sendSuccess(array(
                    'message' => 'Training content link updated successfully',
                    'name' => $name,
                    'content_type' => 'link',
                    'external_url' => $external_url,
                    'credit_assigned' => !empty($credit_id)
                ));
            } else {
                throw new Exception('Update failed: ' . $this->db->error);
            }
            return;
        }
        
        if (empty($name) || !$category_id || !$skill_id) {
            $this->sendError('Name, category, and skill are required', 400);
            return;
        }
        
        // Check if content exists
        $checkStmt = $this->db->prepare("
            SELECT id, name, file_url, thumbnail_url, content_type 
            FROM wp_training_content 
            WHERE id = ? AND is_active = 1
        ");
        if (!$checkStmt) {
            throw new Exception('Check prepare failed: ' . $this->db->error);
        }
        
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $this->sendError('Training content not found', 404);
            return;
        }
        
        $existingContent = $checkResult->fetch_assoc();
        
        // Initialize update variables
        $fileUrl = $existingContent['file_url'];
        $thumbnailUrl = $existingContent['thumbnail_url'];
        $contentType = $existingContent['content_type'];
        
        // Check if new file was uploaded
        if (isset($files['file']) && $files['file']['error'] === UPLOAD_ERR_OK) {
            error_log("New file uploaded, processing...");
            $uploadedFile = $files['file'];
            
            // Setup directories
            $this->setupTrainingContentDirectories();
            
            // Validate file type
            $allowedTypes = array(
                'application/pdf' => 'pdf',
                'application/msword' => 'document',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
                'image/jpeg' => 'image',
                'image/png' => 'image',
                'image/gif' => 'image',
                'image/webp' => 'image',
                'video/mp4' => 'video',
                'video/webm' => 'video',
                'video/quicktime' => 'video'
            );
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
            finfo_close($finfo);
            
            if (!array_key_exists($mimeType, $allowedTypes)) {
                $this->sendError('Invalid file type', 400);
                return;
            }
            
            if ($uploadedFile['size'] > 50 * 1024 * 1024) {
                $this->sendError('File size must be less than 50MB', 400);
                return;
            }
            
            $contentType = $allowedTypes[$mimeType];
            
            // Generate new filename using the existing content ID
            $extension = $this->getFileExtensionFromMime($mimeType);
            $filename = 'content_' . $id . '.' . $extension;
            $filepath = $this->trainingContentDir . '/' . $filename;
            
            // Remove old files first
            $this->cleanupTrainingContentFiles($existingContent['file_url'], $existingContent['thumbnail_url']);
            
            // Move uploaded file
            if (!move_uploaded_file($uploadedFile['tmp_name'], $filepath)) {
                $this->sendError('Failed to save uploaded file', 500);
                return;
            }
            
            // Generate new thumbnail for images
            $thumbnailUrl = null;
            if ($contentType === 'image') {
                $thumbnailFilename = 'thumb_content_' . $id . '.' . $extension;
                $thumbnailPath = $this->trainingContentDir . '/thumbnails/' . $thumbnailFilename;
                
                if ($this->generateThumbnailAtPath($filepath, $thumbnailPath, $mimeType)) {
                    $thumbnailUrl = $this->trainingContentUrl . '/thumbnails/' . $thumbnailFilename;
                }
            }
            
            // Update URLs
            $fileUrl = $this->trainingContentUrl . '/' . $filename;
            
            // Update additional fields for new file with credit
            $updateStmt = $this->db->prepare("
                UPDATE wp_training_content SET 
                name = ?, description = ?, category_id = ?, skill_id = ?, 
                difficulty_level = ?, visibility = ?, content_type = ?, 
                original_filename = ?, file_size = ?, mime_type = ?, 
                file_url = ?, thumbnail_url = ?, credit_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->bind_param('ssiisssssissii', $name, $description, $category_id, $skill_id, 
                                   $difficulty_level, $visibility, $contentType, $uploadedFile['name'], 
                                   $uploadedFile['size'], $mimeType, $fileUrl, $thumbnailUrl, $credit_id, $id);
        } else {
            // Update without new file but with credit
            $updateStmt = $this->db->prepare("
                UPDATE wp_training_content SET 
                name = ?, description = ?, category_id = ?, skill_id = ?, 
                difficulty_level = ?, visibility = ?, credit_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->bind_param('ssiissii', $name, $description, $category_id, $skill_id, 
                                   $difficulty_level, $visibility, $credit_id, $id);
        }
        
        if ($updateStmt->execute()) {
            $this->sendSuccess(array(
                'message' => 'Training content updated successfully',
                'name' => $name,
                'content_type' => $contentType,
                'file_url' => $fileUrl,
                'thumbnail_url' => $thumbnailUrl,
                'credit_assigned' => !empty($credit_id)
            ));
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateTrainingContent error: " . $e->getMessage());
        $this->sendError('Failed to update training content: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete training content (soft delete)
 */
public function deleteTrainingContent($id) {
    try {
        // Get content info before deletion for cleanup
        $stmt = $this->db->prepare("
            SELECT name, file_url, thumbnail_url 
            FROM wp_training_content 
            WHERE id = ? AND is_active = 1
        ");
        
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Clean up files if they exist
                $this->cleanupTrainingContentFiles($row['file_url'], $row['thumbnail_url']);
                
                // Soft delete the content
                $deleteStmt = $this->db->prepare("UPDATE wp_training_content SET is_active = 0, updated_at = NOW() WHERE id = ?");
                if (!$deleteStmt) {
                    throw new Exception('Delete prepare failed: ' . $this->db->error);
                }
                
                $deleteStmt->bind_param('i', $id);
                
                if ($deleteStmt->execute()) {
                    $this->sendSuccess(array(
                        'message' => "Training content '{$row['name']}' deleted successfully"
                    ));
                } else {
                    throw new Exception('Delete failed: ' . $this->db->error);
                }
            } else {
                $this->sendError('Training content not found', 404);
            }
        }
        
    } catch (Exception $e) {
        error_log("deleteTrainingContent error: " . $e->getMessage());
        $this->sendError('Failed to delete training content: ' . $e->getMessage(), 500);
    }
}

/**
 * Helper method to get file extension from MIME type
 */
private function getFileExtensionFromMime($mimeType) {
    $mimeToExt = array(
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov'
    );
    
    return isset($mimeToExt[$mimeType]) ? $mimeToExt[$mimeType] : 'bin';
}

/**
 * Helper method to format file size for display
 */
private function formatFileSize($bytes) {
    if ($bytes === null || $bytes == 0) return '0 B';
    
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = floor(log($bytes, 1024));
    
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}    
/**
 * Clean up old training content files
 */
private function cleanupTrainingContentFiles($fileUrl, $thumbnailUrl) {
    if ($fileUrl) {
        $oldFilePath = str_replace($this->trainingContentUrl, $this->trainingContentDir, $fileUrl);
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
            error_log("Removed old training content file: $oldFilePath");
        }
    }
    
    if ($thumbnailUrl) {
        $oldThumbnailPath = str_replace($this->trainingContentUrl, $this->trainingContentDir, $thumbnailUrl);
        if (file_exists($oldThumbnailPath)) {
            unlink($oldThumbnailPath);
            error_log("Removed old training content thumbnail: $oldThumbnailPath");
        }
    }
}

    /**
     * ASSIGNMENT MANAGEMENT
     */
    
    /**
     * Get a specific assignment by ID
     */
    public function getAssignment($id) {
        $stmt = $this->db->prepare("
            SELECT da.*, d.name as drill_name, d.max_score,
                   dc.display_name as category_display,
                   ds.display_name as skill_display,
                   u.display_name as user_name, u.email as user_email,
                   assigner.display_name as assigned_by_name
            FROM wp_drill_assignments da
            JOIN wp_drills d ON da.drill_id = d.id
            JOIN wp_drill_categories dc ON d.category_id = dc.id
            JOIN wp_drill_skills ds ON d.skill_id = ds.id
            JOIN wp_drill_users u ON da.user_id = u.id
            JOIN wp_drill_users assigner ON da.assigned_by = assigner.id
            WHERE da.id = ? AND da.is_active = 1
        ");
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($assignment = $result->fetch_assoc()) {
            $this->sendSuccess($assignment);
        } else {
            $this->sendError('Assignment not found', 404);
        }
    }
    
    /**
     * Get assignments with enhanced filtering
     */
    public function getAssignments($params = []) {
        $where = ["da.is_active = 1"];
        $bindings = [];
        $types = "";
        
        if (!empty($params['user_id'])) {
            $where[] = "da.user_id = ?";
            $bindings[] = $params['user_id'];
            $types .= "i";
        }
        
        if (!empty($params['drill_id'])) {
            $where[] = "da.drill_id = ?";
            $bindings[] = $params['drill_id'];
            $types .= "i";
        }
        
        if (!empty($params['assigned_by'])) {
            $where[] = "da.assigned_by = ?";
            $bindings[] = $params['assigned_by'];
            $types .= "i";
        }
        
        if (isset($params['is_completed'])) {
            $where[] = "da.is_completed = ?";
            $bindings[] = $params['is_completed'] ? 1 : 0;
            $types .= "i";
        }
        
        // Add date filtering
        if (!empty($params['assigned_after'])) {
            $where[] = "da.assigned_date >= ?";
            $bindings[] = $params['assigned_after'];
            $types .= "s";
        }
        
        if (!empty($params['due_before'])) {
            $where[] = "da.due_date <= ?";
            $bindings[] = $params['due_before'];
            $types .= "s";
        }
        
        $sql = "SELECT da.*, d.name as drill_name, d.max_score, d.description as drill_description,
                       dc.display_name as category_display,
                       ds.display_name as skill_display,
                       u.display_name as user_name, u.email as user_email,
                       assigner.display_name as assigned_by_name,
                       (SELECT COUNT(*) FROM wp_drill_scores ds 
                        WHERE ds.drill_id = da.drill_id AND ds.user_id = da.user_id 
                        AND ds.is_assigned_drill = 1 AND ds.assignment_id = da.id) as score_count
                FROM wp_drill_assignments da
                JOIN wp_drills d ON da.drill_id = d.id
                JOIN wp_drill_categories dc ON d.category_id = dc.id
                JOIN wp_drill_skills ds ON d.skill_id = ds.id
                JOIN wp_drill_users u ON da.user_id = u.id
                JOIN wp_drill_users assigner ON da.assigned_by = assigner.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY da.assigned_date DESC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        $this->sendSuccess($assignments);
    }
    
    /**
     * Create assignment with enhanced validation
     */
    public function createAssignment($data) {
        $user_id = $data['user_id'] ?? 0;
        $drill_id = $data['drill_id'] ?? 0;
        $assigned_by = $data['assigned_by'] ?? 0;
        $due_date = !empty($data['due_date']) ? $data['due_date'] : null;
        $notes = trim($data['notes'] ?? '');
        $coach_comments = trim($data['coach_comments'] ?? '');
        
        if (!$user_id || !$drill_id || !$assigned_by) {
            $this->sendError('User ID, drill ID, and assigned_by are required', 400);
        }
        
        // Verify user exists and is active
        $userCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $userCheck->bind_param('i', $user_id);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            $this->sendError('User not found or inactive', 404);
        }
        
        // Verify drill exists and is active
        $drillCheck = $this->db->prepare("SELECT id, name FROM wp_drills WHERE id = ? AND is_active = 1");
        $drillCheck->bind_param('i', $drill_id);
        $drillCheck->execute();
        $drillResult = $drillCheck->get_result();
        if ($drillResult->num_rows === 0) {
            $this->sendError('Drill not found or inactive', 404);
        }
        
        $drill = $drillResult->fetch_assoc();
        
        // Check for duplicate active assignment
        $duplicateCheck = $this->db->prepare("
            SELECT id FROM wp_drill_assignments 
            WHERE user_id = ? AND drill_id = ? AND is_active = 1
        ");
        $duplicateCheck->bind_param('ii', $user_id, $drill_id);
        $duplicateCheck->execute();
        if ($duplicateCheck->get_result()->num_rows > 0) {
            $this->sendError("This drill is already assigned to the user", 409);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_drill_assignments (user_id, drill_id, assigned_by, due_date, notes, coach_comments) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiisss', $user_id, $drill_id, $assigned_by, $due_date, $notes, $coach_comments);
        
        if ($stmt->execute()) {
            $assignment_id = $this->db->insert_id;
            
            $this->sendSuccess([
                'id' => $assignment_id, 
                'message' => "Drill '{$drill['name']}' assigned successfully",
                'drill_name' => $drill['name']
            ]);
        } else {
            $this->sendError('Failed to create assignment: ' . $this->db->error, 500);
        }
    }
    
    /**
     * Update an existing assignment
     */
    public function updateAssignment($id, $data) {
        // First check if assignment exists
        $checkStmt = $this->db->prepare("
            SELECT da.*, d.name as drill_name, u.display_name as user_name 
            FROM wp_drill_assignments da
            JOIN wp_drills d ON da.drill_id = d.id
            JOIN wp_drill_users u ON da.user_id = u.id
            WHERE da.id = ?
        ");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Assignment not found', 404);
        }
        
        $assignment = $result->fetch_assoc();
        
        // Handle soft delete
        if (isset($data['is_active']) && $data['is_active'] == 0) {
            $stmt = $this->db->prepare("UPDATE wp_drill_assignments SET is_active = 0 WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $this->sendSuccess([
                    'message' => "Assignment for '{$assignment['drill_name']}' removed successfully"
                ]);
            } else {
                $this->sendError('Failed to remove assignment: ' . $this->db->error, 500);
            }
            return;
        }
        
        // Handle other updates
        $due_date = !empty($data['due_date']) ? $data['due_date'] : null;
        $notes = trim($data['notes'] ?? $assignment['notes']);
        $coach_comments = trim($data['coach_comments'] ?? ($assignment['coach_comments'] ?? ''));
        $is_completed = isset($data['is_completed']) ? ($data['is_completed'] ? 1 : 0) : $assignment['is_completed'];
        $completed_date = $is_completed && !$assignment['is_completed'] ? date('Y-m-d H:i:s') : $assignment['completed_date'];
        
        $stmt = $this->db->prepare("
            UPDATE wp_drill_assignments 
            SET due_date = ?, notes = ?, coach_comments = ?, is_completed = ?, completed_date = ?
            WHERE id = ?
        ");
        $stmt->bind_param('sssisi', $due_date, $notes, $coach_comments, $is_completed, $completed_date, $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $this->sendSuccess(['message' => 'Assignment updated successfully']);
            } else {
                $this->sendSuccess(['message' => 'No changes made to assignment']);
            }
        } else {
            $this->sendError('Failed to update assignment: ' . $this->db->error, 500);
        }
    }
    
    /**
     * Delete an assignment (soft delete)
     */
    public function deleteAssignment($id) {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid assignment ID', 400);
        }
        
        // Check if assignment exists
        $checkStmt = $this->db->prepare("
            SELECT da.*, d.name as drill_name, u.display_name as user_name 
            FROM wp_drill_assignments da
            JOIN wp_drills d ON da.drill_id = d.id
            JOIN wp_drill_users u ON da.user_id = u.id
            WHERE da.id = ? AND da.is_active = 1
        ");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Assignment not found or already deleted', 404);
        }
        
        $assignment = $result->fetch_assoc();
        
        // Soft delete the assignment
        $stmt = $this->db->prepare("UPDATE wp_drill_assignments SET is_active = 0 WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $this->sendSuccess([
                    'message' => "Assignment for '{$assignment['drill_name']}' removed from {$assignment['user_name']} successfully"
                ]);
            } else {
                $this->sendError('Failed to remove assignment', 500);
            }
        } else {
            $this->sendError('Failed to remove assignment: ' . $this->db->error, 500);
        }
    }
    
    /**
     * SCORES
     */
    public function getScores($params = []) {
        $where = ["1=1"];
        $bindings = [];
        $types = "";
        
        if (!empty($params['user_id'])) {
            $where[] = "s.user_id = ?";
            $bindings[] = $params['user_id'];
            $types .= "i";
        }
        
        if (!empty($params['drill_id'])) {
            $where[] = "s.drill_id = ?";
            $bindings[] = $params['drill_id'];
            $types .= "i";
        }

		if (!empty($params['start_date'])) {
			$where[] = "s.practice_date >= ?";
			$bindings[] = $params['start_date'];
			$types .= "s";
		}
		
		if (!empty($params['end_date'])) {
			$where[] = "s.practice_date <= ?";
			$bindings[] = $params['end_date'];
			$types .= "s";
		}
        
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        
        $sql = "SELECT s.*, d.name as drill_name,
                       dc.name as category_name, dc.display_name as category_display,
                       ds.name as skill_name, ds.display_name as skill_display,
                       u.display_name as player_name,
                       sub.display_name as submitted_by_name
                FROM wp_drill_scores s
                JOIN wp_drills d ON s.drill_id = d.id
                JOIN wp_drill_categories dc ON d.category_id = dc.id
                JOIN wp_drill_skills ds ON d.skill_id = ds.id
                JOIN wp_drill_users u ON s.user_id = u.id
                JOIN wp_drill_users sub ON s.submitted_by = sub.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY s.submitted_at DESC
                LIMIT ?";
        
        $bindings[] = $limit;
        $types .= "i";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$bindings);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $scores = [];
        while ($row = $result->fetch_assoc()) {
            $scores[] = $row;
        }
        
        $this->sendSuccess($scores);
    }
    
    public function submitScore($data) {
        $user_id = $data['user_id'] ?? 0;
        $drill_id = $data['drill_id'] ?? 0;
        $score = $data['score'] ?? 0;
        $max_score = $data['max_score'] ?? 0;
        $submitted_by = $data['submitted_by'] ?? 0;
        $is_assigned = $data['is_assigned'] ?? 0;
        $assignment_id = $data['assignment_id'] ?? null;
        $notes = $data['notes'] ?? '';
        $practice_date = $data['practice_date'] ?? date('Y-m-d');
        
        if (!$user_id || !$drill_id || !$submitted_by) {
            $this->sendError('User ID, drill ID, and submitted_by are required', 400);
        }
        
        if ($score < 0 || $max_score <= 0) {
            $this->sendError('Invalid score values', 400);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_drill_scores 
            (user_id, drill_id, score, max_possible_score, is_assigned_drill, assignment_id, submitted_by, session_notes, practice_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('iiddiisss', $user_id, $drill_id, $score, $max_score, $is_assigned, $assignment_id, $submitted_by, $notes, $practice_date);
        
        if ($stmt->execute()) {
            // Mark assignment as completed if applicable
            if ($assignment_id) {
                $this->db->query("UPDATE wp_drill_assignments SET is_completed = 1, completed_date = NOW() WHERE id = $assignment_id");
            }
            
            $this->sendSuccess(['id' => $this->db->insert_id, 'message' => 'Score submitted successfully']);
        } else {
            $this->sendError('Failed to submit score: ' . $this->db->error, 500);
        }
    }
    
    /**
     * STATISTICS
     */
    public function getStats($params = []) {
        $user_id = $params['user_id'] ?? null;
        
        $stats = [];
        
        // Total scores
        $sql = "SELECT COUNT(*) as total_scores FROM wp_drill_scores";
        if ($user_id) {
            $sql .= " WHERE user_id = $user_id";
        }
        $result = $this->db->query($sql);
        $stats['total_scores'] = $result->fetch_assoc()['total_scores'];
        
        // Average percentage
        $sql = "SELECT AVG(percentage) as avg_percentage FROM wp_drill_scores";
        if ($user_id) {
            $sql .= " WHERE user_id = $user_id";
        }
        $result = $this->db->query($sql);
        $stats['average_percentage'] = round($result->fetch_assoc()['avg_percentage'], 2);
        
        // Scores by category
        $sql = "SELECT dc.display_name as category, COUNT(*) as count, AVG(s.percentage) as avg_percentage
                FROM wp_drill_scores s
                JOIN wp_drills d ON s.drill_id = d.id
                JOIN wp_drill_categories dc ON d.category_id = dc.id";
        if ($user_id) {
            $sql .= " WHERE s.user_id = $user_id";
        }
        $sql .= " GROUP BY dc.id ORDER BY dc.sort_order";
        
        $result = $this->db->query($sql);
        $stats['by_category'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['by_category'][] = $row;
        }
        
        $this->sendSuccess($stats);
    }
    
    /**
     * JOURNAL MANAGEMENT
     */
    
    /**
     * Get journal entries for a user
     */
    public function getJournalEntries($params = []) {
        $user_id = $params['user_id'] ?? 0;
        
        if (!$user_id) {
            $this->sendError('User ID is required', 400);
        }
        
        // Verify user exists and is active
        $userCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $userCheck->bind_param('i', $user_id);
        $userCheck->execute();
        
        if ($userCheck->get_result()->num_rows === 0) {
            $this->sendError('User not found or inactive', 404);
        }
        
        $stmt = $this->db->prepare("
            SELECT j.*, u.display_name as user_name 
            FROM wp_drill_journal j
            JOIN wp_drill_users u ON j.user_id = u.id
            WHERE j.user_id = ? AND j.is_active = 1
            ORDER BY j.updated_at DESC
        ");
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entries = [];
        while ($row = $result->fetch_assoc()) {
            // Add preview text (first 100 characters)
            $row['preview'] = strlen($row['content']) > 100 ? 
                substr($row['content'], 0, 100) . '...' : 
                $row['content'];
            $entries[] = $row;
        }
        
        $this->sendSuccess($entries);
    }
    
    /**
     * Get a specific journal entry
     */
    public function getJournalEntry($id) {
        $stmt = $this->db->prepare("
            SELECT j.*, u.display_name as user_name 
            FROM wp_drill_journal j
            JOIN wp_drill_users u ON j.user_id = u.id
            WHERE j.id = ? AND j.is_active = 1
        ");
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($entry = $result->fetch_assoc()) {
            $this->sendSuccess($entry);
        } else {
            $this->sendError('Journal entry not found', 404);
        }
    }
    
    /**
     * Create a new journal entry
     */
    public function createJournalEntry($data) {
        $user_id = $data['user_id'] ?? 0;
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        
        if (!$user_id || empty($title) || empty($content)) {
            $this->sendError('User ID, title, and content are required', 400);
        }
        
        // Verify user exists and is active
        $userCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $userCheck->bind_param('i', $user_id);
        $userCheck->execute();
        
        if ($userCheck->get_result()->num_rows === 0) {
            $this->sendError('User not found or inactive', 404);
        }
        
        // Validate title length
        if (strlen($title) > 255) {
            $this->sendError('Title too long (maximum 255 characters)', 400);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_drill_journal (user_id, title, content) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->bind_param('iss', $user_id, $title, $content);
        
        if ($stmt->execute()) {
            $entry_id = $this->db->insert_id;
            
            // Return the created entry
            $this->getJournalEntry($entry_id);
        } else {
            $this->sendError('Failed to create journal entry: ' . $this->db->error, 500);
        }
    }
    
    /**
     * Update an existing journal entry
     */
    public function updateJournalEntry($id, $data) {
        $user_id = $data['user_id'] ?? 0;
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        
        if (!$user_id || empty($title) || empty($content)) {
            $this->sendError('User ID, title, and content are required', 400);
        }
        
        // Validate title length
        if (strlen($title) > 255) {
            $this->sendError('Title too long (maximum 255 characters)', 400);
        }
        
        // Verify the entry belongs to the user
        $ownerCheck = $this->db->prepare("
            SELECT user_id FROM wp_drill_journal 
            WHERE id = ? AND is_active = 1
        ");
        $ownerCheck->bind_param('i', $id);
        $ownerCheck->execute();
        $result = $ownerCheck->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Journal entry not found', 404);
        }
        
        $entry = $result->fetch_assoc();
        if ($entry['user_id'] != $user_id) {
            $this->sendError('Access denied: You can only edit your own journal entries', 403);
        }
        
        $stmt = $this->db->prepare("
            UPDATE wp_drill_journal 
            SET title = ?, content = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param('ssii', $title, $content, $id, $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Return the updated entry
                $this->getJournalEntry($id);
            } else {
                $this->sendError('No changes made to journal entry', 400);
            }
        } else {
            $this->sendError('Failed to update journal entry: ' . $this->db->error, 500);
        }
    }
    
    /**
     * Delete a journal entry (soft delete)
     */
    public function deleteJournalEntry($id) {
        // Get user_id from request data for authorization
        $input = json_decode(file_get_contents('php://input'), true);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($input)) {
            parse_str(file_get_contents('php://input'), $input);
        }
        
        $requesting_user_id = $input['user_id'] ?? 0;
        
        if (!$requesting_user_id) {
            $this->sendError('User ID is required for authorization', 400);
        }
        
        // Verify the entry exists and belongs to the user
        $ownerCheck = $this->db->prepare("
            SELECT user_id, title FROM wp_drill_journal 
            WHERE id = ? AND is_active = 1
        ");
        $ownerCheck->bind_param('i', $id);
        $ownerCheck->execute();
        $result = $ownerCheck->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Journal entry not found', 404);
        }
        
        $entry = $result->fetch_assoc();
        if ($entry['user_id'] != $requesting_user_id) {
            $this->sendError('Access denied: You can only delete your own journal entries', 403);
        }
        
        // Soft delete the entry
        $stmt = $this->db->prepare("
            UPDATE wp_drill_journal 
            SET is_active = 0, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param('ii', $id, $requesting_user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $this->sendSuccess([
                    'message' => "Journal entry '{$entry['title']}' deleted successfully"
                ]);
            } else {
                $this->sendError('Failed to delete journal entry', 500);
            }
        } else {
            $this->sendError('Failed to delete journal entry: ' . $this->db->error, 500);
        }
    }

    /**
     * CHALLENGE EVENTS MANAGEMENT
     */

    /**
     * Get all challenge events with optional filtering
     */
    public function getChallengeEvents($params = []) {
        $where = ["ce.is_active = 1"];
        $bindings = [];
        $types = "";
        
        // Filter by status
        if (!empty($params['status'])) {
            $where[] = "ce.status = ?";
            $bindings[] = $params['status'];
            $types .= "s";
        }
        
        // Filter by series
        if (!empty($params['series_name'])) {
            $where[] = "ce.series_name = ?";
            $bindings[] = $params['series_name'];
            $types .= "s";
        }
        
        // Filter by date range
        if (!empty($params['start_date'])) {
            $where[] = "ce.start_date >= ?";
            $bindings[] = $params['start_date'];
            $types .= "s";
        }
        
        if (!empty($params['end_date'])) {
            $where[] = "ce.end_date <= ?";
            $bindings[] = $params['end_date'];
            $types .= "s";
        }
        
        $sql = "SELECT ce.*, 
                       d.name as drill_name, d.max_score as drill_max_score,
                       dc.display_name as category_display,
                       ds.display_name as skill_display,
                       csm.display_name as scoring_method_display,
                       csm.description as scoring_method_description,
                       creator.display_name as created_by_name,
                       (SELECT COUNT(*) FROM wp_challenge_participants cp 
                        WHERE cp.challenge_event_id = ce.id AND cp.is_active = 1) as participant_count
                FROM wp_challenge_events ce
                JOIN wp_drills d ON ce.drill_id = d.id
                JOIN wp_drill_categories dc ON d.category_id = dc.id
                JOIN wp_drill_skills ds ON d.skill_id = ds.id
                JOIN wp_challenge_scoring_methods csm ON ce.scoring_method_id = csm.id
                JOIN wp_drill_users creator ON ce.created_by = creator.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ce.created_at DESC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        $this->sendSuccess($events);
    }

    /**
     * Get a specific challenge event
     */
    public function getChallengeEvent($id) {
        $stmt = $this->db->prepare("
            SELECT ce.*, 
                   d.name as drill_name, d.max_score as drill_max_score,
                   dc.display_name as category_display,
                   ds.display_name as skill_display,
                   csm.display_name as scoring_method_display,
                   csm.description as scoring_method_description,
                   creator.display_name as created_by_name,
                   (SELECT COUNT(*) FROM wp_challenge_participants cp 
                    WHERE cp.challenge_event_id = ce.id AND cp.is_active = 1) as participant_count
            FROM wp_challenge_events ce
            JOIN wp_drills d ON ce.drill_id = d.id
            JOIN wp_drill_categories dc ON d.category_id = dc.id
            JOIN wp_drill_skills ds ON d.skill_id = ds.id
            JOIN wp_challenge_scoring_methods csm ON ce.scoring_method_id = csm.id
            JOIN wp_drill_users creator ON ce.created_by = creator.id
            WHERE ce.id = ? AND ce.is_active = 1
        ");
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($event = $result->fetch_assoc()) {
            $this->sendSuccess($event);
        } else {
            $this->sendError('Challenge event not found', 404);
        }
    }

    /**
     * Create a new challenge event
     */
    public function createChallengeEvent($data) {
        $series_name = trim($data['series_name'] ?? '');
        $title = trim($data['title'] ?? '');
        $drill_id = $data['drill_id'] ?? 0;
        $scoring_method_id = $data['scoring_method_id'] ?? 0;
        $start_date = $data['start_date'] ?? '';
        $end_date = $data['end_date'] ?? '';
        $status = $data['status'] ?? 'scheduled';
        $description = trim($data['description'] ?? '');
        $max_attempts = $data['max_attempts'] ?? 3;
        $created_by = $data['created_by'] ?? 0;
        $participants = $data['participants'] ?? [];
        
        // Validation
        if (empty($series_name) || empty($title) || !$drill_id || !$scoring_method_id || !$created_by) {
            $this->sendError('Series name, title, drill, scoring method, and creator are required', 400);
        }
        
        if (empty($start_date) || empty($end_date)) {
            $this->sendError('Start date and end date are required', 400);
        }
        
        if ($start_date > $end_date) {
            $this->sendError('End date must be after start date', 400);
        }
        
        // Verify drill exists
        $drillCheck = $this->db->prepare("SELECT id FROM wp_drills WHERE id = ? AND is_active = 1");
        $drillCheck->bind_param('i', $drill_id);
        $drillCheck->execute();
        if ($drillCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid drill selected', 400);
        }
        
        // Verify scoring method exists
        $methodCheck = $this->db->prepare("SELECT id FROM wp_challenge_scoring_methods WHERE id = ? AND is_active = 1");
        $methodCheck->bind_param('i', $scoring_method_id);
        $methodCheck->execute();
        if ($methodCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid scoring method selected', 400);
        }
        
        // Start transaction
        $this->db->begin_transaction();
        
        try {
            // Create the event
            $stmt = $this->db->prepare("
                INSERT INTO wp_challenge_events 
                (series_name, title, drill_id, scoring_method_id, start_date, end_date, status, description, max_attempts, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param('ssisssssii', $series_name, $title, $drill_id, $scoring_method_id, $start_date, $end_date, $status, $description, $max_attempts, $created_by);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create challenge event: ' . $this->db->error);
            }
            
            $event_id = $this->db->insert_id;
            
            // Add participants if provided
            if (!empty($participants) && is_array($participants)) {
                $participantStmt = $this->db->prepare("
                    INSERT INTO wp_challenge_participants (challenge_event_id, user_id, enrolled_by) 
                    VALUES (?, ?, ?)
                ");
                
                foreach ($participants as $participant_id) {
                    $participant_id = (int)$participant_id;
                    if ($participant_id > 0) {
                        $participantStmt->bind_param('iii', $event_id, $participant_id, $created_by);
                        $participantStmt->execute();
                    }
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            $this->sendSuccess([
                'id' => $event_id, 
                'message' => 'Challenge event created successfully',
                'participants_added' => count($participants ?? [])
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            $this->sendError('Failed to create challenge event: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing challenge event
     */
    public function updateChallengeEvent($id, $data) {
        $series_name = trim($data['series_name'] ?? '');
        $title = trim($data['title'] ?? '');
        $drill_id = $data['drill_id'] ?? 0;
        $scoring_method_id = $data['scoring_method_id'] ?? 0;
        $start_date = $data['start_date'] ?? '';
        $end_date = $data['end_date'] ?? '';
        $status = $data['status'] ?? 'scheduled';
        $description = trim($data['description'] ?? '');
        $max_attempts = $data['max_attempts'] ?? 3;
        $participants = $data['participants'] ?? [];
        
        // Validation
        if (empty($series_name) || empty($title) || !$drill_id || !$scoring_method_id) {
            $this->sendError('Series name, title, drill, and scoring method are required', 400);
        }
        
        if (empty($start_date) || empty($end_date)) {
            $this->sendError('Start date and end date are required', 400);
        }
        
        if ($start_date > $end_date) {
            $this->sendError('End date must be after start date', 400);
        }
        
        // Check if event exists
        $checkStmt = $this->db->prepare("SELECT id FROM wp_challenge_events WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $this->sendError('Challenge event not found', 404);
        }
        
        // Start transaction
        $this->db->begin_transaction();
        
        try {
            // Update the event
            $stmt = $this->db->prepare("
                UPDATE wp_challenge_events 
                SET series_name = ?, title = ?, drill_id = ?, scoring_method_id = ?, 
                    start_date = ?, end_date = ?, status = ?, description = ?, max_attempts = ?, 
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->bind_param('ssisssssii', $series_name, $title, $drill_id, $scoring_method_id, $start_date, $end_date, $status, $description, $max_attempts, $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update challenge event: ' . $this->db->error);
            }
            
            // Update participants if provided
            if (isset($data['participants']) && is_array($participants)) {
                // First, deactivate all current participants
                $deactivateStmt = $this->db->prepare("UPDATE wp_challenge_participants SET is_active = 0 WHERE challenge_event_id = ?");
                $deactivateStmt->bind_param('i', $id);
                $deactivateStmt->execute();
                
                // Then add the new participants
                if (!empty($participants)) {
                    $participantStmt = $this->db->prepare("
                        INSERT INTO wp_challenge_participants (challenge_event_id, user_id, enrolled_by) 
                        VALUES (?, ?, 1)
                        ON DUPLICATE KEY UPDATE is_active = 1, enrolled_date = NOW()
                    ");
                    
                    foreach ($participants as $participant_id) {
                        $participant_id = (int)$participant_id;
                        if ($participant_id > 0) {
                            $participantStmt->bind_param('ii', $id, $participant_id);
                            $participantStmt->execute();
                        }
                    }
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            $this->sendSuccess([
                'message' => 'Challenge event updated successfully',
                'participants_updated' => isset($data['participants'])
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            $this->sendError('Failed to update challenge event: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a challenge event (soft delete)
     */
    public function deleteChallengeEvent($id) {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid challenge event ID', 400);
        }
        
        // Check if event exists
        $checkStmt = $this->db->prepare("SELECT id, title FROM wp_challenge_events WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Challenge event not found', 404);
        }
        
        $event = $result->fetch_assoc();
        
        // Start transaction for safe deletion
        $this->db->begin_transaction();
        
        try {
            // Soft delete the event
            $stmt = $this->db->prepare("UPDATE wp_challenge_events SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete challenge event: ' . $this->db->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('No changes made to challenge event');
            }
            
            // Deactivate participants (but keep scores for history)
            $participantStmt = $this->db->prepare("UPDATE wp_challenge_participants SET is_active = 0 WHERE challenge_event_id = ?");
            $participantStmt->bind_param('i', $id);
            $participantStmt->execute();
            
            // Commit transaction
            $this->db->commit();
            
            $this->sendSuccess(['message' => "Challenge event '{$event['title']}' deleted successfully. Historical scores have been preserved."]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            $this->sendError('Failed to delete challenge event: ' . $e->getMessage(), 500);
        }
    }

    /**
     * CHALLENGE SCORING METHODS
     */
    public function getChallengeScoringMethods() {
        $sql = "SELECT * FROM wp_challenge_scoring_methods WHERE is_active = 1 ORDER BY display_name";
        $result = $this->db->query($sql);
        $methods = [];
        
        while ($row = $result->fetch_assoc()) {
            $methods[] = $row;
        }
        
        $this->sendSuccess($methods);
    }

    /**
     * CHALLENGE PARTICIPANTS
     */
    public function getChallengeParticipants($params = []) {
        $where = ["cp.is_active = 1"];
        $bindings = [];
        $types = "";
        
        if (!empty($params['event_id'])) {
            $where[] = "cp.challenge_event_id = ?";
            $bindings[] = $params['event_id'];
            $types .= "i";
        }
        
        if (!empty($params['user_id'])) {
            $where[] = "cp.user_id = ?";
            $bindings[] = $params['user_id'];
            $types .= "i";
        }
        
        $sql = "SELECT cp.*, 
                       u.display_name as user_name, u.email as user_email,
                       ce.title as event_title, ce.series_name,
                       enrolledBy.display_name as enrolled_by_name
                FROM wp_challenge_participants cp
                JOIN wp_drill_users u ON cp.user_id = u.id
                JOIN wp_challenge_events ce ON cp.challenge_event_id = ce.id
                JOIN wp_drill_users enrolledBy ON cp.enrolled_by = enrolledBy.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cp.enrolled_date DESC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $participants = [];
        while ($row = $result->fetch_assoc()) {
            $participants[] = $row;
        }
        
        $this->sendSuccess($participants);
    }

    /**
     * Add a participant to a challenge event
     */
    public function addChallengeParticipant($data) {
        $event_id = $data['event_id'] ?? 0;
        $user_id = $data['user_id'] ?? 0;
        $enrolled_by = $data['enrolled_by'] ?? 0;
        
        if (!$event_id || !$user_id || !$enrolled_by) {
            $this->sendError('Event ID, user ID, and enrolled_by are required', 400);
        }
        
        // Check if event exists
        $eventCheck = $this->db->prepare("SELECT id FROM wp_challenge_events WHERE id = ? AND is_active = 1");
        $eventCheck->bind_param('i', $event_id);
        $eventCheck->execute();
        if ($eventCheck->get_result()->num_rows === 0) {
            $this->sendError('Challenge event not found', 404);
        }
        
        // Check if user exists
        $userCheck = $this->db->prepare("SELECT id FROM wp_drill_users WHERE id = ? AND is_active = 1");
        $userCheck->bind_param('i', $user_id);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            $this->sendError('User not found', 404);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_challenge_participants (challenge_event_id, user_id, enrolled_by) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_active = 1, enrolled_date = NOW()
        ");
        
        $stmt->bind_param('iii', $event_id, $user_id, $enrolled_by);
        
        if ($stmt->execute()) {
            $this->sendSuccess(['id' => $this->db->insert_id, 'message' => 'Participant added successfully']);
        } else {
            $this->sendError('Failed to add participant: ' . $this->db->error, 500);
        }
    }

    /**
     * Remove a participant from a challenge event
     */
    public function removeChallengeParticipant($participantId) {
        $stmt = $this->db->prepare("UPDATE wp_challenge_participants SET is_active = 0 WHERE id = ?");
        $stmt->bind_param('i', $participantId);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $this->sendSuccess(['message' => 'Participant removed successfully']);
            } else {
                $this->sendError('Participant not found', 404);
            }
        } else {
            $this->sendError('Failed to remove participant: ' . $this->db->error, 500);
        }
    }

    /**
     * CHALLENGE SCORES MANAGEMENT
     */

    /**
     * Get challenge scores
     */
    public function getChallengeScores($params = []) {
        $where = ["1=1"];
        $bindings = [];
        $types = "";
        
        if (!empty($params['user_id'])) {
            $where[] = "cs.user_id = ?";
            $bindings[] = $params['user_id'];
            $types .= "i";
        }
        
        if (!empty($params['challenge_event_id'])) {
            $where[] = "cs.challenge_event_id = ?";
            $bindings[] = $params['challenge_event_id'];
            $types .= "i";
        }
        
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        
        $sql = "SELECT cs.*, ce.title as event_title, ce.series_name,
                       d.name as drill_name, d.max_score as drill_max_score,
                       u.display_name as user_name,
                       sub.display_name as submitted_by_name
                FROM wp_challenge_scores cs
                JOIN wp_challenge_events ce ON cs.challenge_event_id = ce.id
                JOIN wp_drills d ON cs.drill_id = d.id
                JOIN wp_drill_users u ON cs.user_id = u.id
                JOIN wp_drill_users sub ON cs.submitted_by = sub.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cs.submitted_at DESC
                LIMIT ?";
        
        $bindings[] = $limit;
        $types .= "i";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$bindings);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $scores = [];
        while ($row = $result->fetch_assoc()) {
            $scores[] = $row;
        }
        
        $this->sendSuccess($scores);
    }

    /**
     * Submit a challenge score
     */
    public function submitChallengeScore($data) {
        $challenge_event_id = $data['challenge_event_id'] ?? 0;
        $user_id = $data['user_id'] ?? 0;
        $drill_id = $data['drill_id'] ?? 0;
        $score = $data['score'] ?? 0;
        $max_possible_score = $data['max_possible_score'] ?? 0;
        $submitted_by = $data['submitted_by'] ?? 0;
        $attempt_number = $data['attempt_number'] ?? 1;
        $notes = $data['notes'] ?? '';
        $practice_date = $data['practice_date'] ?? date('Y-m-d');
        
        if (!$challenge_event_id || !$user_id || !$drill_id || !$submitted_by) {
            $this->sendError('Challenge event ID, user ID, drill ID, and submitted_by are required', 400);
        }
        
        if ($score < 0 || $max_possible_score <= 0) {
            $this->sendError('Invalid score values', 400);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_challenge_scores 
            (challenge_event_id, user_id, drill_id, score, max_possible_score, attempt_number, session_notes, practice_date, submitted_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('iiiddiiss', $challenge_event_id, $user_id, $drill_id, $score, $max_possible_score, $attempt_number, $notes, $practice_date, $submitted_by);
        
        if ($stmt->execute()) {
            $this->sendSuccess(['id' => $this->db->insert_id, 'message' => 'Challenge score submitted successfully']);
        } else {
            $this->sendError('Failed to submit challenge score: ' . $this->db->error, 500);
        }
    }
    
    /**
     * DIAGRAM MANAGEMENT - From current version
     */
    
    /**
     * Get all diagrams with filtering
     */

/**
 * Enhanced getDiagrams with vector support
 */
public function getDiagrams($params = array()) {
    try {
        $where = ["d.is_active = 1"];
        $bindings = [];
        $types = "";
        
        // Filter by diagram type
        if (!empty($params['diagram_type'])) {
            $where[] = "d.diagram_type = ?";
            $bindings[] = $params['diagram_type'];
            $types .= "s";
        }
        
        // Filter by visibility
        if (!empty($params['visibility'])) {
            $where[] = "d.visibility = ?";
            $bindings[] = $params['visibility'];
            $types .= "s";
        }
        
        // Filter by format (vector vs image)
        if (!empty($params['format'])) {
            if ($params['format'] === 'vector') {
                $where[] = "d.is_vector = 1";
            } elseif ($params['format'] === 'image') {
                $where[] = "d.is_vector = 0";
            }
        }
        
        // Search filter
        if (!empty($params['search'])) {
            $where[] = "(d.name LIKE ? OR d.description LIKE ?)";
            $searchTerm = '%' . $params['search'] . '%';
            $bindings[] = $searchTerm;
            $bindings[] = $searchTerm;
            $types .= "ss";
        }
        
        $sql = "SELECT d.id, d.name, d.description, d.diagram_type, d.visibility,
                       d.original_filename, d.created_by, d.created_at, d.updated_at,
                       d.image_url, d.thumbnail_url, d.vector_data, d.is_vector,
                       u.display_name as created_by_name
                FROM wp_diagrams d
                LEFT JOIN wp_drill_users u ON d.created_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY d.created_at DESC";
        
        if (!empty($bindings)) {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param($types, ...$bindings);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
            if (!$result) {
                throw new Exception('Query failed: ' . $this->db->error);
            }
        }
        
        $diagrams = array();
        while ($row = $result->fetch_assoc()) {
            // Ensure created_by_name has a fallback
            if (!$row['created_by_name']) {
                $row['created_by_name'] = 'Unknown User';
            }
            
            $diagrams[] = $row;
        }
        
        error_log("getDiagrams returning " . count($diagrams) . " diagrams");
        $this->sendSuccess($diagrams);
        
    } catch (Exception $e) {
        error_log("getDiagrams error: " . $e->getMessage());
        $this->sendError('Failed to load diagrams: ' . $e->getMessage(), 500);
    }
}
    
    /**
     * Get a specific diagram by ID
     */

/**
 * Enhanced getDiagram with vector support
 */
public function getDiagram($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT d.*, u.display_name as created_by_name 
            FROM wp_diagrams d
            LEFT JOIN wp_drill_users u ON d.created_by = u.id
            WHERE d.id = ? AND d.is_active = 1
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($diagram = $result->fetch_assoc()) {
            // Ensure created_by_name has a fallback
            if (!$diagram['created_by_name']) {
                $diagram['created_by_name'] = 'Unknown User';
            }
            
            $this->sendSuccess($diagram);
        } else {
            $this->sendError('Diagram not found', 404);
        }
        
    } catch (Exception $e) {
        error_log("getDiagram error: " . $e->getMessage());
        $this->sendError('Failed to load diagram: ' . $e->getMessage(), 500);
    }
}
    
    /**
     * Create a new diagram with file upload - Using ID-based filenames
     */

/**
 * Enhanced createDiagram with vector support
 */
public function createDiagram() {
    try {
        error_log("createDiagram called with vector support");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $diagram_type = isset($_POST['diagram_type']) ? $_POST['diagram_type'] : 'drill';
        $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'private';
        $created_by = isset($_POST['created_by']) ? intval($_POST['created_by']) : null;
        $is_vector = isset($_POST['is_vector']) ? intval($_POST['is_vector']) : 0;
        $vector_data = isset($_POST['vector_data']) ? trim($_POST['vector_data']) : '';
        
        error_log("Parsed data: name='$name', type='$diagram_type', visibility='$visibility', user=$created_by, is_vector=$is_vector");
        
        // Validation
        if (empty($name)) {
            $this->sendError('Name is required', 400);
            return;
        }
        
        if (!$created_by) {
            $this->sendError('User authentication required', 401);
            return;
        }
        
        // Validate based on diagram type
        if ($is_vector) {
            // Vector diagram validation
            if (empty($vector_data)) {
                $this->sendError('Vector data is required for vector diagrams', 400);
                return;
            }
            
            // Basic SVG validation
            if (!$this->isValidSVG($vector_data)) {
                $this->sendError('Invalid SVG vector data provided', 400);
                return;
            }
            
            error_log("Processing vector diagram with " . strlen($vector_data) . " characters of SVG data");
            
            // Insert vector diagram record
            $stmt = $this->db->prepare("
                INSERT INTO wp_diagrams 
                (name, description, diagram_type, visibility, created_by, is_vector, vector_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception('Insert prepare failed: ' . $this->db->error);
            }
            
            $stmt->bind_param('sssssis', $name, $description, $diagram_type, $visibility, $created_by, $is_vector, $vector_data);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert vector diagram: ' . $this->db->error);
            }
            
            $diagramId = $this->db->insert_id;
            error_log("Vector diagram created successfully with ID: $diagramId");
            
            $this->sendSuccess(array(
                'id' => $diagramId, 
                'message' => 'Vector diagram created successfully',
                'diagram_type' => $diagram_type,
                'visibility' => $visibility,
                'is_vector' => true,
                'vector_data_length' => strlen($vector_data)
            ));
            
        } else {
            // Image diagram validation
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                error_log("File upload error: " . (isset($_FILES['image']) ? $_FILES['image']['error'] : 'no file'));
                $this->sendError('Image file is required for image diagrams', 400);
                return;
            }
            
            $imageFile = $_FILES['image'];
            error_log("Processing uploaded image file: " . $imageFile['name'] . " (" . $imageFile['size'] . " bytes)");
            
            // Validate file type
            $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imageFile['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $this->sendError('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed', 400);
                return;
            }
            
            // Validate file size (10MB limit)
            if ($imageFile['size'] > 10 * 1024 * 1024) {
                $this->sendError('File size must be less than 10MB', 400);
                return;
            }
            
            // Insert image diagram record first to get the ID
            $stmt = $this->db->prepare("
                INSERT INTO wp_diagrams 
                (name, description, diagram_type, visibility, original_filename, created_by, is_vector) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception('Insert prepare failed: ' . $this->db->error);
            }
            
            $stmt->bind_param('sssssii', $name, $description, $diagram_type, $visibility, $imageFile['name'], $created_by, $is_vector);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert image diagram record: ' . $this->db->error);
            }
            
            $diagramId = $this->db->insert_id;
            error_log("Database insert successful. Diagram ID: $diagramId");
            
            // Generate filenames based on the diagram ID
            $extension = $this->getExtensionFromMimeType($mimeType);
            $filename = $this->generateFilenameFromId($diagramId, $extension);
            $filepath = $this->uploadsDir . '/' . $filename;
            
            error_log("Moving file to: $filepath");
            
            // Move uploaded file to final location
            if (!move_uploaded_file($imageFile['tmp_name'], $filepath)) {
                // If file move fails, clean up the database record
                $this->db->query("DELETE FROM wp_diagrams WHERE id = $diagramId");
                error_log("Failed to move uploaded file, cleaned up database record");
                $this->sendError('Failed to save uploaded file', 500);
                return;
            }
            
            // Generate thumbnail
            $thumbnailFilename = $this->generateThumbnailFilenameFromId($diagramId, $extension);
            $thumbnailPath = $this->uploadsDir . '/thumbnails/' . $thumbnailFilename;
            $thumbnailUrl = null;
            
            if ($this->generateThumbnailAtPath($filepath, $thumbnailPath, $mimeType)) {
                $thumbnailUrl = $this->uploadsUrl . '/thumbnails/' . $thumbnailFilename;
            }
            
            // Update the record with file URLs
            $imageUrl = $this->uploadsUrl . '/' . $filename;
            $updateStmt = $this->db->prepare("
                UPDATE wp_diagrams 
                SET image_url = ?, thumbnail_url = ? 
                WHERE id = ?
            ");
            
            if (!$updateStmt) {
                throw new Exception('Update prepare failed: ' . $this->db->error);
            }
            
            $updateStmt->bind_param('ssi', $imageUrl, $thumbnailUrl, $diagramId);
            
            if (!$updateStmt->execute()) {
                error_log("Failed to update URLs in database: " . $this->db->error);
            }
            
            error_log("Image file saved successfully. Image URL: $imageUrl");
            
            $this->sendSuccess(array(
                'id' => $diagramId, 
                'message' => 'Image diagram uploaded successfully',
                'diagram_type' => $diagram_type,
                'visibility' => $visibility,
                'is_vector' => false,
                'image_url' => $imageUrl,
                'thumbnail_url' => $thumbnailUrl,
                'filename' => $filename
            ));
        }
        
    } catch (Exception $e) {
        error_log("createDiagram error: " . $e->getMessage());
        $this->sendError('Failed to create diagram: ' . $e->getMessage(), 500);
    }
}

    
    /**
     * Update an existing diagram - Enhanced to handle PUT requests properly
     */

/**
 * Enhanced updateDiagram with vector support
 */
public function updateDiagram($id) {
    try {
        error_log("updateDiagram called with ID: $id (with vector support)");
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        
        // Handle different request methods
        $putData = array();
        $files = array();
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            error_log("Processing PUT request");
            
            // Check if it's multipart form data
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                error_log("Parsing multipart form data for PUT");
                $this->parseMultipartFormData($putData, $files);
            } else {
                // Handle URL-encoded PUT data
                error_log("Parsing URL-encoded PUT data");
                parse_str(file_get_contents('php://input'), $putData);
            }
        } else {
            // Handle as POST request
            error_log("Processing as POST request");
            $putData = $_POST;
            $files = $_FILES;
        }
        
        error_log("Parsed PUT data: " . print_r($putData, true));
        error_log("Parsed FILES data: " . print_r($files, true));
        
        $name = isset($putData['name']) ? trim($putData['name']) : '';
        $description = isset($putData['description']) ? trim($putData['description']) : '';
        $diagram_type = isset($putData['diagram_type']) ? $putData['diagram_type'] : 'drill';
        $visibility = isset($putData['visibility']) ? $putData['visibility'] : 'private';
        $is_vector = isset($putData['is_vector']) ? intval($putData['is_vector']) : 0;
        $vector_data = isset($putData['vector_data']) ? trim($putData['vector_data']) : '';
        
        error_log("Extracted values: name='$name', type='$diagram_type', visibility='$visibility', is_vector=$is_vector");
        
        if (empty($name)) {
            error_log("Validation failed: Name is empty");
            $this->sendError('Name is required', 400);
            return;
        }
        
        // Check if diagram exists and get current info
        $checkStmt = $this->db->prepare("
            SELECT id, name, image_url, thumbnail_url, is_vector, vector_data 
            FROM wp_diagrams 
            WHERE id = ? AND is_active = 1
        ");
        if (!$checkStmt) {
            throw new Exception('Check prepare failed: ' . $this->db->error);
        }
        
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            error_log("Diagram not found for ID: $id");
            $this->sendError('Diagram not found', 404);
            return;
        }
        
        $existingDiagram = $checkResult->fetch_assoc();
        error_log("Found existing diagram: " . $existingDiagram['name'] . " (current is_vector: " . $existingDiagram['is_vector'] . ")");
        
        // Initialize update variables
        $imageUrl = $existingDiagram['image_url'];
        $thumbnailUrl = $existingDiagram['thumbnail_url'];
        $currentVectorData = $existingDiagram['vector_data'];
        
        if ($is_vector) {
            // Updating to/as vector diagram
            if (empty($vector_data)) {
                $this->sendError('Vector data is required for vector diagrams', 400);
                return;
            }
            
            if (!$this->isValidSVG($vector_data)) {
                $this->sendError('Invalid SVG vector data provided', 400);
                return;
            }
            
            error_log("Updating as vector diagram");
            
            // If switching from image to vector, clean up old files
            if ($existingDiagram['is_vector'] == 0) {
                $this->cleanupImageFiles($existingDiagram['image_url'], $existingDiagram['thumbnail_url']);
                $imageUrl = null;
                $thumbnailUrl = null;
            }
            
            // Update with vector data
            $stmt = $this->db->prepare("
                UPDATE wp_diagrams SET 
                name = ?, description = ?, diagram_type = ?, visibility = ?, 
                is_vector = ?, vector_data = ?, image_url = ?, thumbnail_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if (!$stmt) {
                throw new Exception('Update prepare failed: ' . $this->db->error);
            }
            
            $stmt->bind_param('ssssisssi', $name, $description, $diagram_type, $visibility, $is_vector, $vector_data, $imageUrl, $thumbnailUrl, $id);
            
        } else {
            // Updating as image diagram
            error_log("Updating as image diagram");
            
            // Check if new image was uploaded
            if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
                error_log("New image uploaded, processing...");
                $imageFile = $files['image'];
                
                // Validate file
                $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $imageFile['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    error_log("Invalid file type: $mimeType");
                    $this->sendError('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed', 400);
                    return;
                }
                
                if ($imageFile['size'] > 10 * 1024 * 1024) {
                    error_log("File too large: " . $imageFile['size']);
                    $this->sendError('File size must be less than 10MB', 400);
                    return;
                }
                
                // Generate new filenames using the existing diagram ID
                $extension = $this->getExtensionFromMimeType($mimeType);
                $filename = $this->generateFilenameFromId($id, $extension);
                $filepath = $this->uploadsDir . '/' . $filename;
                
                error_log("Saving new image to: $filepath");
                
                // Remove old files first
                $this->cleanupImageFiles($existingDiagram['image_url'], $existingDiagram['thumbnail_url']);
                
                // Move uploaded file
                if (!move_uploaded_file($imageFile['tmp_name'], $filepath)) {
                    error_log("Failed to save uploaded file");
                    $this->sendError('Failed to save uploaded file', 500);
                    return;
                }
                
                // Generate new thumbnail
                $thumbnailFilename = $this->generateThumbnailFilenameFromId($id, $extension);
                $thumbnailPath = $this->uploadsDir . '/thumbnails/' . $thumbnailFilename;
                
                if ($this->generateThumbnailAtPath($filepath, $thumbnailPath, $mimeType)) {
                    $thumbnailUrl = $this->uploadsUrl . '/thumbnails/' . $thumbnailFilename;
                }
                
                // Update URLs
                $imageUrl = $this->uploadsUrl . '/' . $filename;
                
                error_log("New image saved successfully. URL: $imageUrl");
            }
            
            // If switching from vector to image, clear vector data
            if ($existingDiagram['is_vector'] == 1) {
                $currentVectorData = null;
            }
            
            // Update with image data (with or without new image)
            $stmt = $this->db->prepare("
                UPDATE wp_diagrams SET 
                name = ?, description = ?, diagram_type = ?, visibility = ?, 
                is_vector = ?, vector_data = ?, image_url = ?, thumbnail_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if (!$stmt) {
                throw new Exception('Update prepare failed: ' . $this->db->error);
            }
            
            $stmt->bind_param('ssssisssi', $name, $description, $diagram_type, $visibility, $is_vector, $currentVectorData, $imageUrl, $thumbnailUrl, $id);
        }
        
        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            error_log("Update successful - affected rows: $affectedRows");
            
            $this->sendSuccess(array(
                'message' => 'Diagram updated successfully',
                'name' => $name,
                'diagram_type' => $diagram_type,
                'visibility' => $visibility,
                'is_vector' => $is_vector,
                'image_url' => $imageUrl,
                'thumbnail_url' => $thumbnailUrl,
                'vector_data_length' => $is_vector ? strlen($vector_data) : 0,
                'affected_rows' => $affectedRows
            ));
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateDiagram error: " . $e->getMessage());
        $this->sendError('Failed to update diagram: ' . $e->getMessage(), 500);
    }
}
    
/**
 * Validate SVG data
 */
private function isValidSVG($data) {
    // Basic validation - check if it contains SVG tags
    if (empty($data)) {
        return false;
    }
    
    // Check for basic SVG structure
    if (!preg_match('/<svg[^>]*>/i', $data) || !preg_match('/<\/svg>/i', $data)) {
        return false;
    }
    
    // Additional security check - ensure no script tags
    if (preg_match('/<script[^>]*>/i', $data)) {
        error_log("SVG validation failed: contains script tags");
        return false;
    }
    
    // Check for suspicious JavaScript-like content
    if (preg_match('/javascript:/i', $data) || preg_match('/on\w+\s*=/i', $data)) {
        error_log("SVG validation failed: contains suspicious JavaScript");
        return false;
    }
    
    return true;
}


/**
 * Clean up old image files
 */
private function cleanupImageFiles($imageUrl, $thumbnailUrl) {
    if ($imageUrl) {
        $oldImagePath = str_replace($this->uploadsUrl, $this->uploadsDir, $imageUrl);
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath);
            error_log("Removed old image: $oldImagePath");
        }
    }
    
    if ($thumbnailUrl) {
        $oldThumbnailPath = str_replace($this->uploadsUrl, $this->uploadsDir, $thumbnailUrl);
        if (file_exists($oldThumbnailPath)) {
            unlink($oldThumbnailPath);
            error_log("Removed old thumbnail: $oldThumbnailPath");
        }
    }
}


    /**
     * Delete a diagram (soft delete) 
     */

/**
 * Enhanced deleteDiagram (unchanged but included for completeness)
 */
public function deleteDiagram($id) {
    try {
        // Get diagram info before deletion for cleanup
        $stmt = $this->db->prepare("
            SELECT image_url, thumbnail_url 
            FROM wp_diagrams 
            WHERE id = ? AND is_active = 1
        ");
        
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Clean up files if they exist
                $this->cleanupImageFiles($row['image_url'], $row['thumbnail_url']);
            }
        }
        
        // Soft delete the diagram
        $deleteStmt = $this->db->prepare("UPDATE wp_diagrams SET is_active = 0, updated_at = NOW() WHERE id = ?");
        if (!$deleteStmt) {
            throw new Exception('Delete prepare failed: ' . $this->db->error);
        }
        
        $deleteStmt->bind_param('i', $id);
        
        if ($deleteStmt->execute()) {
            $this->sendSuccess(array('message' => 'Diagram deleted successfully'));
        } else {
            throw new Exception('Delete failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("deleteDiagram error: " . $e->getMessage());
        $this->sendError('Failed to delete diagram: ' . $e->getMessage(), 500);
    }
}
    
    /**
     * Debug file storage
     */
    public function debugFileStorage() {
        try {
            // Check table
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'wp_diagrams'");
            $tableExists = $tableCheck->num_rows > 0;
            
            // Check directories
            $directoryInfo = array(
                'uploads_dir' => array(
                    'path' => $this->uploadsDir,
                    'exists' => is_dir($this->uploadsDir),
                    'writable' => is_writable($this->uploadsDir)
                ),
                'thumbnails_dir' => array(
                    'path' => $this->uploadsDir . '/thumbnails',
                    'exists' => is_dir($this->uploadsDir . '/thumbnails'),
                    'writable' => is_dir($this->uploadsDir . '/thumbnails') ? is_writable($this->uploadsDir . '/thumbnails') : false
                )
            );
            
            $this->sendSuccess(array(
                'table_exists' => $tableExists,
                'directory_info' => $directoryInfo,
                'php_upload_settings' => array(
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'file_uploads' => ini_get('file_uploads') ? 'enabled' : 'disabled'
                ),
                'gd_available' => extension_loaded('gd')
            ));
            
        } catch (Exception $e) {
            $this->sendError('Debug failed: ' . $e->getMessage(), 500);
        }
    }


/**
 * Get all achievement types
 */
public function getAchievementTypes() {
    $result = $this->db->query("
        SELECT id, name, description, calculation_method, is_active 
        FROM wp_achievement_types 
        WHERE is_active = 1 
        ORDER BY name
    ");
    
    $types = [];
    while ($row = $result->fetch_assoc()) {
        // Add level count
        $levelCount = $this->db->query("SELECT COUNT(*) as count FROM wp_achievement_levels WHERE achievement_type_id = {$row['id']}");
        $row['level_count'] = $levelCount->fetch_assoc()['count'];
        $types[] = $row;
    }
    
    $this->sendSuccess($types);
}

/**
 * Get achievement schemes (formatted for selection)
 */
public function getAchievementSchemes() {
    $result = $this->db->query("
        SELECT 
            at.id, at.name, at.description, at.calculation_method,
            COUNT(al.id) as level_count,
            GROUP_CONCAT(al.level_name ORDER BY al.level_number SEPARATOR ', ') as level_names
        FROM wp_achievement_types at
        LEFT JOIN wp_achievement_levels al ON at.id = al.achievement_type_id
        WHERE at.is_active = 1
        GROUP BY at.id
        ORDER BY at.name
    ");
    
    $schemes = [];
    while ($row = $result->fetch_assoc()) {
        $schemes[] = $row;
    }
    
    $this->sendSuccess($schemes);
}

/**
 * Get levels for specific achievement type
 */
public function getAchievementLevels($typeId) {
    $stmt = $this->db->prepare("
        SELECT * FROM wp_achievement_levels 
        WHERE achievement_type_id = ? 
        ORDER BY level_number
    ");
    $stmt->bind_param('i', $typeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $levels = [];
    while ($row = $result->fetch_assoc()) {
        $levels[] = $row;
    }
    
    $this->sendSuccess($levels);
}

/**
 * Calculate user achievement level
 */
public function calculateUserAchievementLevel($userId, $drillId, $userScore = null) {
    // Get drill info
    $drillStmt = $this->db->prepare("
        SELECT id, name, max_score, achievement_type_id
        FROM wp_drills WHERE id = ? AND is_active = 1
    ");
    $drillStmt->bind_param('i', $drillId);
    $drillStmt->execute();
    $drillResult = $drillStmt->get_result();
    
    if (!$drill = $drillResult->fetch_assoc()) {
        $this->sendError('Drill not found', 404);
        return;
    }
    
    if (!$drill['achievement_type_id']) {
        $this->sendError('No achievement system assigned to this drill', 400);
        return;
    }
    
    // Get user's best score if not provided
    if ($userScore === null) {
        $scoreStmt = $this->db->prepare("
            SELECT MAX(score) as best_score 
            FROM wp_drill_scores 
            WHERE user_id = ? AND drill_id = ?
        ");
        $scoreStmt->bind_param('ii', $userId, $drillId);
        $scoreStmt->execute();
        $scoreData = $scoreStmt->get_result()->fetch_assoc();
        $userScore = $scoreData['best_score'];
    }
    
    if ($userScore === null) {
        $this->sendError('No scores found for this user and drill', 404);
        return;
    }
    
    // Get achievement type info
    $typeStmt = $this->db->prepare("SELECT name, calculation_method FROM wp_achievement_types WHERE id = ?");
    $typeStmt->bind_param('i', $drill['achievement_type_id']);
    $typeStmt->execute();
    $typeResult = $typeStmt->get_result();
    $achievementType = $typeResult->fetch_assoc();
    
    // Calculate comparison value
    if ($achievementType['calculation_method'] === 'percentage') {
        $comparisonValue = ($userScore / $drill['max_score']) * 100;
    } else {
        $comparisonValue = $userScore;
    }
    
    // Get levels and find matching one
    $levelsStmt = $this->db->prepare("SELECT * FROM wp_achievement_levels WHERE achievement_type_id = ? ORDER BY level_number");
    $levelsStmt->bind_param('i', $drill['achievement_type_id']);
    $levelsStmt->execute();
    $levelsResult = $levelsStmt->get_result();
    
    $matchedLevel = null;
    while ($level = $levelsResult->fetch_assoc()) {
        if ($comparisonValue >= $level['min_threshold'] && $comparisonValue <= $level['max_threshold']) {
            $matchedLevel = $level;
            break;
        }
    }
    
    if ($matchedLevel) {
        $this->sendSuccess([
            'level_name' => $matchedLevel['level_name'],
            'level_number' => $matchedLevel['level_number'],
            'display_color' => $matchedLevel['display_color'],
            'display_icon' => $matchedLevel['display_icon'],
            'comparison_value' => $comparisonValue,
            'achievement_type' => $achievementType['name'],
            'calculation_method' => $achievementType['calculation_method'],
            'user_score' => $userScore,
            'drill_max_score' => $drill['max_score']
        ]);
    } else {
        $this->sendError('No matching achievement level found', 404);
    }
}

/**
 * Get a specific achievement type by ID
 */
public function getAchievementType($id) {
    try {
        $stmt = $this->db->prepare("
            SELECT at.*, 
                   COUNT(al.id) as level_count
            FROM wp_achievement_types at
            LEFT JOIN wp_achievement_levels al ON at.id = al.achievement_type_id
            WHERE at.id = ? AND at.is_active = 1
            GROUP BY at.id
        ");
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($achievementType = $result->fetch_assoc()) {
            $this->sendSuccess($achievementType);
        } else {
            $this->sendError('Achievement type not found', 404);
        }
        
    } catch (Exception $e) {
        error_log("getAchievementType error: " . $e->getMessage());
        $this->sendError('Failed to load achievement type: ' . $e->getMessage(), 500);
    }
}

/**
 * Create a new achievement type
 */
public function createAchievementType($data) {
    try {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $calculation_method = $data['calculation_method'] ?? '';
        
        // Validation
        if (empty($name)) {
            $this->sendError('Achievement type name is required', 400);
            return;
        }
        
        if (!in_array($calculation_method, ['percentage', 'score'])) {
            $this->sendError('Valid calculation method (percentage or score) is required', 400);
            return;
        }
        
        // Check for duplicate name
        $duplicateCheck = $this->db->prepare("SELECT id FROM wp_achievement_types WHERE name = ? AND is_active = 1");
        $duplicateCheck->bind_param('s', $name);
        $duplicateCheck->execute();
        if ($duplicateCheck->get_result()->num_rows > 0) {
            $this->sendError('An achievement type with this name already exists', 409);
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_achievement_types (name, description, calculation_method) 
            VALUES (?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Insert prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('sss', $name, $description, $calculation_method);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert achievement type: ' . $this->db->error);
        }
        
        $typeId = $this->db->insert_id;
        error_log("Achievement type created successfully with ID: $typeId");
        
        $this->sendSuccess([
            'id' => $typeId, 
            'message' => 'Achievement type created successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("createAchievementType error: " . $e->getMessage());
        $this->sendError('Failed to create achievement type: ' . $e->getMessage(), 500);
    }
}

/**
 * Update an existing achievement type
 */
public function updateAchievementType($id, $data) {
    try {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $calculation_method = $data['calculation_method'] ?? '';
        
        // Validation
        if (empty($name)) {
            $this->sendError('Achievement type name is required', 400);
            return;
        }
        
        if (!in_array($calculation_method, ['percentage', 'score'])) {
            $this->sendError('Valid calculation method (percentage or score) is required', 400);
            return;
        }
        
        // Check if achievement type exists
        $checkStmt = $this->db->prepare("SELECT id FROM wp_achievement_types WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            $this->sendError('Achievement type not found', 404);
            return;
        }
        
        // Check for duplicate name (excluding current record)
        $duplicateCheck = $this->db->prepare("SELECT id FROM wp_achievement_types WHERE name = ? AND id != ? AND is_active = 1");
        $duplicateCheck->bind_param('si', $name, $id);
        $duplicateCheck->execute();
        if ($duplicateCheck->get_result()->num_rows > 0) {
            $this->sendError('An achievement type with this name already exists', 409);
            return;
        }
        
        $stmt = $this->db->prepare("
            UPDATE wp_achievement_types 
            SET name = ?, description = ?, calculation_method = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('sssi', $name, $description, $calculation_method, $id);
        
        if ($stmt->execute()) {
            $this->sendSuccess([
                'message' => 'Achievement type updated successfully'
            ]);
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateAchievementType error: " . $e->getMessage());
        $this->sendError('Failed to update achievement type: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete an achievement type (soft delete)
 */
public function deleteAchievementType($id) {
    try {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid achievement type ID', 400);
            return;
        }
        
        // Check if achievement type exists
        $checkStmt = $this->db->prepare("SELECT id, name FROM wp_achievement_types WHERE id = ? AND is_active = 1");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Achievement type not found', 404);
            return;
        }
        
        $achievementType = $result->fetch_assoc();
        
        // Check if achievement type is being used by any drills
        $usageCheck = $this->db->prepare("SELECT COUNT(*) as count FROM wp_drills WHERE achievement_type_id = ? AND is_active = 1");
        $usageCheck->bind_param('i', $id);
        $usageCheck->execute();
        $usageResult = $usageCheck->get_result();
        $usageCount = $usageResult->fetch_assoc()['count'];
        
        if ($usageCount > 0) {
            $this->sendError("Cannot delete achievement type '{$achievementType['name']}' because it is assigned to $usageCount drill(s). Please remove the assignments first.", 409);
            return;
        }
        
        // Start transaction for safe deletion
        $this->db->begin_transaction();
        
        try {
            // Soft delete the achievement type
            $stmt = $this->db->prepare("UPDATE wp_achievement_types SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete achievement type: ' . $this->db->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('No changes made to achievement type');
            }
            
            // Also deactivate all levels for this achievement type
            $levelsStmt = $this->db->prepare("UPDATE wp_achievement_levels SET is_active = 0, updated_at = NOW() WHERE achievement_type_id = ?");
            $levelsStmt->bind_param('i', $id);
            $levelsStmt->execute();
            
            // Commit transaction
            $this->db->commit();
            
            $this->sendSuccess(['message' => "Achievement type '{$achievementType['name']}' deleted successfully."]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("deleteAchievementType error: " . $e->getMessage());
        $this->sendError('Failed to delete achievement type: ' . $e->getMessage(), 500);
    }
}

/**
 * Create a new achievement level
 */
public function createAchievementLevel($data) {
    try {
        $achievement_type_id = $data['achievement_type_id'] ?? 0;
        $level_name = trim($data['level_name'] ?? '');
        $level_number = $data['level_number'] ?? 0;
        $min_threshold = $data['min_threshold'] ?? 0;
        $max_threshold = $data['max_threshold'] ?? null;
        $target_threshold = $data['target_score'] ?? null; // Note: form sends target_score but DB stores target_threshold
        $display_color = trim($data['display_color'] ?? '#4CAF50');
        $display_icon = trim($data['display_icon'] ?? 'fa-trophy');
        $icon_type = 'fontawesome'; // Default to fontawesome for now
        
        // Validation
        if (!$achievement_type_id || empty($level_name) || !$level_number) {
            $this->sendError('Achievement type ID, level name, and level number are required', 400);
            return;
        }
        
        if ($min_threshold < 0) {
            $this->sendError('Minimum threshold cannot be negative', 400);
            return;
        }
        
        if ($max_threshold !== null && $max_threshold <= $min_threshold) {
            $this->sendError('Maximum threshold must be greater than minimum threshold', 400);
            return;
        }
        
        // Verify achievement type exists
        $typeCheck = $this->db->prepare("SELECT id FROM wp_achievement_types WHERE id = ? AND is_active = 1");
        $typeCheck->bind_param('i', $achievement_type_id);
        $typeCheck->execute();
        if ($typeCheck->get_result()->num_rows === 0) {
            $this->sendError('Invalid achievement type specified', 400);
            return;
        }
        
        // Check for duplicate level number in this achievement type
        $duplicateCheck = $this->db->prepare("SELECT id FROM wp_achievement_levels WHERE achievement_type_id = ? AND level_number = ?");
        $duplicateCheck->bind_param('ii', $achievement_type_id, $level_number);
        $duplicateCheck->execute();
        if ($duplicateCheck->get_result()->num_rows > 0) {
            $this->sendError('A level with this number already exists for this achievement type', 409);
            return;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO wp_achievement_levels 
            (achievement_type_id, level_number, level_name, min_threshold, max_threshold, target_threshold, display_color, display_icon, icon_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Insert prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('iisdddsss', $achievement_type_id, $level_number, $level_name, $min_threshold, $max_threshold, $target_threshold, $display_color, $display_icon, $icon_type);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert achievement level: ' . $this->db->error);
        }
        
        $levelId = $this->db->insert_id;
        error_log("Achievement level created successfully with ID: $levelId");
        
        $this->sendSuccess([
            'id' => $levelId, 
            'message' => 'Achievement level created successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("createAchievementLevel error: " . $e->getMessage());
        $this->sendError('Failed to create achievement level: ' . $e->getMessage(), 500);
    }
}

/**
 * Update an existing achievement level
 */
public function updateAchievementLevel($id, $data) {
    try {
        $level_name = trim($data['level_name'] ?? '');
        $level_number = $data['level_number'] ?? 0;
        $min_threshold = $data['min_threshold'] ?? 0;
        $max_threshold = $data['max_threshold'] ?? null;
        $target_threshold = $data['target_score'] ?? null; // Note: form sends target_score but DB stores target_threshold
        $display_color = trim($data['display_color'] ?? '#4CAF50');
        $display_icon = trim($data['display_icon'] ?? 'fa-trophy');
        
        // Validation
        if (empty($level_name) || !$level_number) {
            $this->sendError('Level name and level number are required', 400);
            return;
        }
        
        if ($min_threshold < 0) {
            $this->sendError('Minimum threshold cannot be negative', 400);
            return;
        }
        
        if ($max_threshold !== null && $max_threshold <= $min_threshold) {
            $this->sendError('Maximum threshold must be greater than minimum threshold', 400);
            return;
        }
        
        // Check if achievement level exists and get achievement_type_id
        $checkStmt = $this->db->prepare("SELECT id, achievement_type_id FROM wp_achievement_levels WHERE id = ?");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Achievement level not found', 404);
            return;
        }
        
        $existingLevel = $result->fetch_assoc();
        $achievement_type_id = $existingLevel['achievement_type_id'];
        
        // Check for duplicate level number in this achievement type (excluding current record)
        $duplicateCheck = $this->db->prepare("SELECT id FROM wp_achievement_levels WHERE achievement_type_id = ? AND level_number = ? AND id != ?");
        $duplicateCheck->bind_param('iii', $achievement_type_id, $level_number, $id);
        $duplicateCheck->execute();
        if ($duplicateCheck->get_result()->num_rows > 0) {
            $this->sendError('A level with this number already exists for this achievement type', 409);
            return;
        }
        
        $stmt = $this->db->prepare("
            UPDATE wp_achievement_levels 
            SET level_number = ?, level_name = ?, min_threshold = ?, max_threshold = ?, target_threshold = ?, 
                display_color = ?, display_icon = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Update prepare failed: ' . $this->db->error);
        }
        
        $stmt->bind_param('isdddssi', $level_number, $level_name, $min_threshold, $max_threshold, $target_threshold, $display_color, $display_icon, $id);
        
        if ($stmt->execute()) {
            $this->sendSuccess([
                'message' => 'Achievement level updated successfully'
            ]);
        } else {
            throw new Exception('Update failed: ' . $this->db->error);
        }
        
    } catch (Exception $e) {
        error_log("updateAchievementLevel error: " . $e->getMessage());
        $this->sendError('Failed to update achievement level: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete an achievement level (hard delete - levels can be safely removed)
 */
public function deleteAchievementLevel($id) {
    try {
        // Ensure ID is an integer
        $id = (int)$id;
        
        if ($id <= 0) {
            $this->sendError('Invalid achievement level ID', 400);
            return;
        }
        
        // Check if achievement level exists
        $checkStmt = $this->db->prepare("SELECT id, level_name FROM wp_achievement_levels WHERE id = ?");
        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->sendError('Achievement level not found', 404);
            return;
        }
        
        $level = $result->fetch_assoc();
        
        // Hard delete the level (levels are safe to completely remove)
        $stmt = $this->db->prepare("DELETE FROM wp_achievement_levels WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $this->sendSuccess(['message' => "Achievement level '{$level['level_name']}' deleted successfully."]);
            } else {
                $this->sendError('Failed to delete achievement level', 500);
            }
        } else {
            $this->sendError('Failed to delete achievement level: ' . $this->db->error, 500);
        }
        
    } catch (Exception $e) {
        error_log("deleteAchievementLevel error: " . $e->getMessage());
        $this->sendError('Failed to delete achievement level: ' . $e->getMessage(), 500);
    }
}
    
    /**
     * VERSION INFO
     */
    public function getVersion() {
        $this->sendSuccess([
            'version' => '2.9-complete-merged',
            'name' => 'Drill Score API - Complete Merged Version',
            'features' => [
                'admin-login' => 'Database-driven admin authentication',
                'drill-management' => 'Full CRUD operations for drills',
                'user-management' => 'User accounts and assignments',
                'score-tracking' => 'Persistent score storage',
                'journal-system' => 'Personal training journal for students',
                'challenge-events' => 'Competition and challenge event management',
                'challenge-participants' => 'Participant enrollment and management',
                'challenge-scoring' => 'Multiple scoring methods for events',
                'challenge-scores' => 'Challenge-specific score tracking and submission',
                'assignment-management' => 'Complete drill assignment system with admin interface',
                'diagram-management' => 'File-based diagram upload and management with thumbnails',
				'content-assignments' => 'Training content assignment management system',
				'training-content-management' => 'File-based training content upload and management with thumbnails'
            ],
            'status' => 'operational',
            'uploads_dir' => $this->uploadsDir ?? 'not initialized',
            'uploads_url' => $this->uploadsUrl ?? 'not initialized',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    


    /**
     * UTILITY METHODS
     */
    private function sendSuccess($data = null, $message = 'Success') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error_code' => $code
        ]);
        exit();
    }
}

// Main execution with error handling
try {
    error_log("Starting API execution");
    $api = new DrillAPI();
    $api->handleRequest();
} catch (Exception $e) {
    error_log("Fatal API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Server error occurred',
        'error_code' => 500
    ));
}
?>
        