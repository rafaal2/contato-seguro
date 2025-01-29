<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class ProductService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId, $filters = [])
    {
        $query = "
            SELECT 
                p.*, 
                c.title AS category
            FROM 
                product p
            LEFT JOIN 
                product_category pc ON pc.product_id = p.id
            LEFT JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.company_id = (
                    SELECT company_id FROM admin_user WHERE id = :admin_user_id
                )
        ";

        if (isset($filters['active'])) {
            $query .= " AND p.active = :active";
        }

        if (isset($filters['category_id'])) {
            $query .= " AND c.id = :category_id";
        }

        $query .= " GROUP BY p.id ORDER BY p.created_at DESC";

        $stm = $this->pdo->prepare($query);


        $stm->bindParam(':admin_user_id', $adminUserId, \PDO::PARAM_INT);


        if (isset($filters['active'])) {
            $stm->bindParam(':active', $filters['active'], \PDO::PARAM_INT);
        }

        if (isset($filters['category_id'])) {
            $stm->bindParam(':category_id', $filters['category_id'], \PDO::PARAM_INT);
        }

        $stm->execute();
        return $stm;
    }

    public function getOne($id, $adminUserId)
    {
        $query = "
        SELECT 
            p.*, 
            GROUP_CONCAT(c.title) AS categories
        FROM 
            product p
        LEFT JOIN 
            product_category pc ON pc.product_id = p.id
        LEFT JOIN 
            category c ON c.id = pc.cat_id
        WHERE 
            p.id = :id
            AND p.company_id = (
                SELECT company_id FROM admin_user WHERE id = :admin_user_id
            )
        GROUP BY 
            p.id
    ";

        $stm = $this->pdo->prepare($query);
        $stm->bindParam(':id', $id, \PDO::PARAM_INT);
        $stm->bindParam(':admin_user_id', $adminUserId, \PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetch(\PDO::FETCH_ASSOC);
    }


    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO product (
                company_id,
                title,
                price,
                active
            ) VALUES (
                {$body['company_id']},
                '{$body['title']}',
                {$body['price']},
                {$body['active']}
            )
        ");
        if (!$stm->execute())
            return false;

        $productId = $this->pdo->lastInsertId();

        $stm = $this->pdo->prepare("
            INSERT INTO product_category (
                product_id,
                cat_id
            ) VALUES (
                {$productId},
                {$body['category_id']}
            );
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$productId},
                {$adminUserId},
                'create'
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            UPDATE product
            SET company_id = {$body['company_id']},
                title = '{$body['title']}',
                price = {$body['price']},
                active = {$body['active']}
            WHERE id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = {$body['category_id']}
            WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'update'
            )
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE FROM product_category WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'delete'
            )
        ");

        return $stm->execute();
    }

    public function getLog($productId)
    {
        $query = "
    SELECT 
        pl.action,
        pl.timestamp,
        au.name AS admin_user_name
    FROM 
        product_log pl
    INNER JOIN 
        admin_user au ON pl.admin_user_id = au.id
    WHERE 
        pl.product_id = :product_id
    ";

        $stm = $this->pdo->prepare($query);
        $stm->bindParam(':product_id', $productId, \PDO::PARAM_INT);
        $stm->execute();

        return $stm;
    }
    public function getLastPriceChange($productId)
    {
        $query = "
        SELECT 
            pl.admin_user_id,
            au.name AS admin_user_name,
            pl.timestamp
        FROM 
            product_log pl
        INNER JOIN 
            admin_user au ON pl.admin_user_id = au.id
        WHERE 
            pl.product_id = :product_id
            AND pl.action = 'update'
        ORDER BY 
            pl.timestamp DESC
        LIMIT 1
    ";

        $stm = $this->pdo->prepare($query);
        $stm->bindParam(':product_id', $productId, \PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetch(\PDO::FETCH_ASSOC);
    }
}
