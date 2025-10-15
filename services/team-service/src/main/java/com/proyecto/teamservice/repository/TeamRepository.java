package com.proyecto.teamservice.repository;

import com.proyecto.teamservice.model.Team;
import org.springframework.data.jpa.repository.JpaRepository;

public interface TeamRepository extends JpaRepository<Team, Integer> {
    boolean existsByNameIgnoreCase(String name);
    org.springframework.data.domain.Page<Team> findByNameContainingIgnoreCase(String name, org.springframework.data.domain.Pageable pageable);
}
