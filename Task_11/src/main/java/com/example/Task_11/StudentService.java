package com.example.Task_11;

import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.data.domain.Sort;
import org.springframework.stereotype.Service;

@Service
public class StudentService {

    @Autowired
    private StudentRepository stuRep;

    public Student saveStudent(Student student) {
        return stuRep.save(student);
    }

    public List<Student> getAllStudents() {
        return stuRep.findAll();
    }

    public List<Student> getByDepartment(String department) {
        return stuRep.findByDepartment(department);
    }

    public List<Student> getByAge(int age) {
        return stuRep.findByAgeGreaterThan(age);
    }

    public List<Student> sortByName() {
        return stuRep.findAll(Sort.by(Sort.Direction.ASC, "name"));
    }

    public List<Student> sortByAge() {
        return stuRep.findAll(Sort.by(Sort.Direction.ASC, "age"));
    }

    public Page<Student> getByPage(int page, int size) {
        Pageable pageable = PageRequest.of(page, size);
        return stuRep.findAll(pageable);
    }
}