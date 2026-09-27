USE atit_attendance;

-- Create subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_code VARCHAR(50) NOT NULL UNIQUE,
    subject_name VARCHAR(200) NOT NULL,
    description TEXT,
    course_id INT NOT NULL,
    lecturer_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Create indexes for foreign keys
CREATE INDEX idx_subjects_course ON subjects(course_id);
CREATE INDEX idx_subjects_lecturer ON subjects(lecturer_id);
CREATE INDEX idx_subjects_status ON subjects(status);
