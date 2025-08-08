<?php
require_once 'config/database.php';

class HomeManager {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Add a new home for a homeowner
    public function addHome($homeowner_id, $home_details) {
        try {
            $this->conn->beginTransaction();

            // First, create the home record
            $home_query = "INSERT INTO home (homeowner_id) VALUES (:homeowner_id)";
            $home_stmt = $this->conn->prepare($home_query);
            $home_stmt->bindParam(":homeowner_id", $homeowner_id);
            $home_stmt->execute();
            
            $home_id = $this->conn->lastInsertId();

            // Then, create the home_details record
            $details_query = "INSERT INTO home_details (
                home_id, home_name, num_of_bedrooms, washrooms, rent_monthly, 
                utility_bills, facilities, family_bachelor_status, address, 
                city, state, zip_code, description
            ) VALUES (
                :home_id, :home_name, :num_of_bedrooms, :washrooms, :rent_monthly,
                :utility_bills, :facilities, :family_bachelor_status, :address,
                :city, :state, :zip_code, :description
            )";

            $details_stmt = $this->conn->prepare($details_query);
            $details_stmt->bindParam(":home_id", $home_id);
            $details_stmt->bindParam(":home_name", $home_details['home_name']);
            $details_stmt->bindParam(":num_of_bedrooms", $home_details['num_of_bedrooms']);
            $details_stmt->bindParam(":washrooms", $home_details['washrooms']);
            $details_stmt->bindParam(":rent_monthly", $home_details['rent_monthly']);
            $details_stmt->bindParam(":utility_bills", $home_details['utility_bills']);
            $details_stmt->bindParam(":facilities", $home_details['facilities']);
            $details_stmt->bindParam(":family_bachelor_status", $home_details['family_bachelor_status']);
            $details_stmt->bindParam(":address", $home_details['address']);
            $details_stmt->bindParam(":city", $home_details['city']);
            $details_stmt->bindParam(":state", $home_details['state']);
            $details_stmt->bindParam(":zip_code", $home_details['zip_code']);
            $details_stmt->bindParam(":description", $home_details['description']);
            $details_stmt->execute();

            // Update homeowner's number_of_homes
            $update_query = "UPDATE homeowners SET number_of_homes = number_of_homes + 1 WHERE id = :homeowner_id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(":homeowner_id", $homeowner_id);
            $update_stmt->execute();

            $this->conn->commit();
            return ['success' => true, 'home_id' => $home_id, 'message' => 'Home added successfully'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to add home: ' . $e->getMessage()];
        }
    }

    // Get all homes for a homeowner
    public function getHomesByHomeowner($homeowner_id) {
        $query = "SELECT 
                    h.id as home_id,
                    hd.id as detail_id,
                    hd.home_name,
                    hd.num_of_bedrooms,
                    hd.washrooms,
                    hd.rent_monthly,
                    hd.utility_bills,
                    hd.facilities,
                    hd.family_bachelor_status,
                    hd.address,
                    hd.city,
                    hd.state,
                    hd.zip_code,
                    hd.description,
                    hd.is_available,
                    hd.created_at
                  FROM home h
                  JOIN home_details hd ON h.id = hd.home_id
                  WHERE h.homeowner_id = :homeowner_id
                  ORDER BY hd.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":homeowner_id", $homeowner_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get a specific home by ID
    public function getHomeById($home_id) {
        $query = "SELECT 
                    h.id as home_id,
                    hd.id as detail_id,
                    hd.home_name,
                    hd.num_of_bedrooms,
                    hd.washrooms,
                    hd.rent_monthly,
                    hd.utility_bills,
                    hd.facilities,
                    hd.family_bachelor_status,
                    hd.address,
                    hd.city,
                    hd.state,
                    hd.zip_code,
                    hd.description,
                    hd.is_available,
                    hd.created_at
                  FROM home h
                  JOIN home_details hd ON h.id = hd.home_id
                  WHERE h.id = :home_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":home_id", $home_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update home details
    public function updateHomeDetails($home_id, $home_details) {
        $query = "UPDATE home_details SET 
                    home_name = :home_name,
                    num_of_bedrooms = :num_of_bedrooms,
                    washrooms = :washrooms,
                    rent_monthly = :rent_monthly,
                    utility_bills = :utility_bills,
                    facilities = :facilities,
                    family_bachelor_status = :family_bachelor_status,
                    address = :address,
                    city = :city,
                    state = :state,
                    zip_code = :zip_code,
                    description = :description,
                    updated_at = CURRENT_TIMESTAMP
                  WHERE home_id = :home_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":home_id", $home_id);
        $stmt->bindParam(":home_name", $home_details['home_name']);
        $stmt->bindParam(":num_of_bedrooms", $home_details['num_of_bedrooms']);
        $stmt->bindParam(":washrooms", $home_details['washrooms']);
        $stmt->bindParam(":rent_monthly", $home_details['rent_monthly']);
        $stmt->bindParam(":utility_bills", $home_details['utility_bills']);
        $stmt->bindParam(":facilities", $home_details['facilities']);
        $stmt->bindParam(":family_bachelor_status", $home_details['family_bachelor_status']);
        $stmt->bindParam(":address", $home_details['address']);
        $stmt->bindParam(":city", $home_details['city']);
        $stmt->bindParam(":state", $home_details['state']);
        $stmt->bindParam(":zip_code", $home_details['zip_code']);
        $stmt->bindParam(":description", $home_details['description']);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Home updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update home'];
        }
    }

    // Delete a home
    public function deleteHome($home_id, $homeowner_id) {
        try {
            $this->conn->beginTransaction();

            // Delete the home (cascade will delete home_details and images)
            $delete_query = "DELETE FROM home WHERE id = :home_id AND homeowner_id = :homeowner_id";
            $delete_stmt = $this->conn->prepare($delete_query);
            $delete_stmt->bindParam(":home_id", $home_id);
            $delete_stmt->bindParam(":homeowner_id", $homeowner_id);
            $delete_stmt->execute();

            if ($delete_stmt->rowCount() > 0) {
                // Update homeowner's number_of_homes
                $update_query = "UPDATE homeowners SET number_of_homes = GREATEST(number_of_homes - 1, 0) WHERE id = :homeowner_id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(":homeowner_id", $homeowner_id);
                $update_stmt->execute();

                $this->conn->commit();
                return ['success' => true, 'message' => 'Home deleted successfully'];
            } else {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Home not found or not authorized'];
            }

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to delete home: ' . $e->getMessage()];
        }
    }

    // Toggle home availability
    public function toggleHomeAvailability($home_id, $homeowner_id) {
        $query = "UPDATE home_details hd 
                  JOIN home h ON hd.home_id = h.id 
                  SET hd.is_available = NOT hd.is_available 
                  WHERE h.id = :home_id AND h.homeowner_id = :homeowner_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":home_id", $home_id);
        $stmt->bindParam(":homeowner_id", $homeowner_id);

        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Home availability updated'];
        } else {
            return ['success' => false, 'message' => 'Failed to update availability'];
        }
    }

    // Get all available homes (for tenants to browse)
    public function getAllAvailableHomes() {
        $query = "SELECT 
                    h.id as home_id,
                    hd.id as detail_id,
                    hd.home_name,
                    hd.num_of_bedrooms,
                    hd.washrooms,
                    hd.rent_monthly,
                    hd.utility_bills,
                    hd.facilities,
                    hd.family_bachelor_status,
                    hd.address,
                    hd.city,
                    hd.state,
                    hd.zip_code,
                    hd.description,
                    hd.created_at,
                    ho.first_name as homeowner_first_name,
                    ho.last_name as homeowner_last_name,
                    ho.phone as homeowner_phone
                  FROM home h
                  JOIN home_details hd ON h.id = hd.home_id
                  JOIN homeowners ho ON h.homeowner_id = ho.id
                  WHERE hd.is_available = 1
                  ORDER BY hd.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 