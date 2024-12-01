<?php
class GroupUtils {
    public static function get_group_coins( $group_id ): int {
        $dbconnection = DBConnection::get_connection();
        $sql = "SELECT SUM(coins) FROM group_coins WHERE group_id = ?";
        $stmt = $dbconnection->prepare( $sql );
        $stmt->execute([ $group_id ] );
        return $stmt->fetchColumn(0) ?? 0;
    }

    public static function get_group_coins_per_user( $group_id, $user_id ):int {
        $dbconnection = DBConnection::get_connection();
        $sql = "SELECT SUM(coins) FROM group_coins WHERE group_id = ? AND user_id = ?";
        $stmt = $dbconnection->prepare( $sql );
        $stmt->execute([ $group_id, Auth::user()['user_id'] ] );
        return $stmt->fetchColumn(0) ?? 0;
    }
}
?>