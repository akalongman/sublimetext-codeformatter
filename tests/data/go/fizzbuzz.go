package main 
import "fmt"
// Only formatting is messed up
func main() {
for i := 1; i <= 100; i++ {
fmt.Println(map[bool]map[bool]interface{}{
false: {false: i, true: "Fizz"}, true: {false: "Buzz", true: "FizzBuzz"},
}[i%5 == 0][i%3 == 0])
}
}

